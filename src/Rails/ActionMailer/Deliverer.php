<?php
namespace Rails\ActionMailer;

use stdClass;
use Finfo;
use Zend\Mail;
use Zend\Mime;
use Rails;
use Rails\ActionView;

/**
 * Builds and delivers mail.
 *
 * This class should only be used by Rails\ActionMailer\Base.
 * In order to create a custom Mail, Zend\Mail should be used
 * directly instead.
 */
class Deliverer
{
    /**
     * Rails mail that will be processed.
     *
     * @var Rails\ActionMailer\Base
     */
    protected $mail;
    
    /**
     * Mail message that will be delivered.
     *
     * @var Zend\Mail\Message
     */
    protected $message;
    
    protected $textTemplate;
    
    protected $htmlTemplate;
    
    /**
     * @var Zend\Mime\Message
     */
    protected $body;
    
    protected $actionView;
    
    public function __construct(Base $mail)
    {
        $this->mail = $mail;
        $this->buildMessage();
    }
    
    public function deliver()
    {
        ActionMailer::transport()->send($this->message);
        return $this;
    }
    
    public function mail()
    {
        return $this->mail;
    }
    
    public function message()
    {
        return $this->message;
    }
    
    protected function buildMessage()
    {
        $this->message = new Mail\Message();
        
        $this->setCharset();
        $this->setFrom();
        $this->setTo();
        $this->setSubject();
        
        // ActionView\ViewHelpers::load();
        $this->createMailParts();
        
        $this->body = new Mime\Message();
        
        $this->addTemplates();
        $this->addAttachments();
        
        $this->message->setBody($this->body);
        
        /**
         * Set content-type to alternative if both text and html are being send.
         */
        if ($this->textTemplate && $this->htmlTemplate) {
            $this->message
                ->getHeaders()
                    ->get('content-type')
                        ->setType('multipart/alternative');
        }
        
        unset($this->textTemplate, $this->htmlTemplate);
    }
    
    protected function setCharset()
    {
        if (!$charset = $this->mail->charset) {
            $charset = mb_detect_encoding($this->mail->subject);
            if (!$charset)
                $charset = null;
        }
        
        $this->message->setEncoding($charset);
    }
    
    protected function setFrom()
    {
        if (!is_array($this->mail->from)) {
            $email = $this->mail->from;
            $name = null;
        } else {
            list($email, $name) = $this->mail->from;
        }
        
        $this->message->setFrom($email, $name);
    }
    
    protected function setTo()
    {
        $this->message->addTo($this->mail->to);
    }
    
    protected function setSubject()
    {
        $this->message->setSubject($this->mail->subject);
    }
    
    protected function createTextPart()
    {
        $template = $this->actionView->finder()->findTemplate(
            $this->mail->templateName,
            false,
            [$this->mail->templatePath],
            [],
            ['txt'],
            ['php']
        );
        
        if ($template)  {
            $contents = $this->actionView->renderer()->renderTemplate(
                $template,
                false,
                $this->mail->assigns,
                $this->actionView->helperSet(),
                new \ArrayObject()
            );
            $this->textTemplate = $contents;
        }
    }
    
    protected function createHtmlPart()
    {
        $template = $this->actionView->finder()->findTemplate(
            $this->mail->templateName,
            false,
            [$this->mail->templatePath],
            [],
            ['html'],
            array_keys($this->actionView->handlers())
        );
        
        if ($template)  {
            $contents = $this->actionView->renderer()->renderTemplate(
                $template,
                false,
                $this->mail->assigns,
                $this->actionView->helperSet(),
                new \ArrayObject()
            );
            $this->htmlTemplate = $contents;
        }
    }
    
    protected function templateBasename()
    {
        return Rails::config()['paths']['views'] . '/' .
               $this->mail->templatePath . '/' .
               $this->mail->templateName;
    }
    
    protected function addTemplates()
    {
        if ($this->textTemplate) {
            $part = new Mime\Part($this->textTemplate);
            $part->type = 'text/plain';
            $part->encoding = Mime\Mime::ENCODING_QUOTEDPRINTABLE;
            $this->body->addPart($part);
        }
        
        if ($this->htmlTemplate) {
            $part = new Mime\Part($this->htmlTemplate);
            $part->type = 'text/html';
            $part->encoding = Mime\Mime::ENCODING_QUOTEDPRINTABLE;
            $this->body->addPart($part);
        }
    }
    
    /**
     * Requires Fileinfo.
     */
    protected function addAttachments()
    {
        if (class_exists('Finfo', false)) {
            $finfo = new \Finfo(FILEINFO_MIME_TYPE);
        } else {
            $finfo = null;
        }
        
        foreach ($this->mail->attachments as $filename => $attachment) {
            if (!is_array($attachment)) {
                throw new Exception\RuntimeException(
                    sprintf("Attachments must be array, %s passed", gettype($attachment))
                );
            } elseif (
                !is_string($attachment['content']) &&
                (
                 !is_resource($attachment['content']) ||
                 !get_resource_type($attachment['content']) == 'stream'
                )
            ) {
                throw new Exception\RuntimeException(
                    sprintf(
                        "Attachment content must be string or stream, %s passed",
                        gettype($attachment['content'])
                    )
                );
            }
            
            $type = null;
            
            if (empty($attachment['mime_type']) && $finfo) {
                if (is_resource($attachment['content'])) {
                    $type = $finfo->buffer(stream_get_contents($attachment['content']));
                    rewind($attachment['content']);
                } else {
                    $type = $finfo->buffer($attachment['content']);
                }
            }
            
            $part = new Mime\Part($attachment['content']);
            
            if (empty($attachment['encoding'])) {
                $attachment['encoding'] = Mime\Mime::ENCODING_BASE64;
            }
            
            $part->encoding = $attachment['encoding'];
            
            if ($type) {
                $part->type = $type;
            }
            
            if (!empty($attachment['inline'])) {
                $part->disposition = Mime\Mime::DISPOSITION_INLINE;
            } else {
                $part->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
            }
            
            $this->body->addPart($part);
        }
    }
    
    protected function createMailParts()
    {
        $this->actionView = new ActionView\ActionView();
        $this->actionView->lookupContext()->addPath(Rails::application()->config()['paths']['app']->expand('views'));
        
        $this->createTextPart();
        $this->createHtmlPart();
    }
}
