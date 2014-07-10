<?php
namespace Rails\ActiveSupport;

use Carbon\Carbon as Base;
use Rails\ActiveSupport\Exception;
use Rails\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Extends Carbon to add Rails-like, localized diffForHumans().
 */
class Carbon extends Base
{
    use ServiceLocatorAwareTrait;
    
    public function diffForHumans(Base $other = null, $locale = null)
    {
        $isNow = $other === null;

        if ($isNow) {
            $other = static::now($this->tz);
        }
        
        $isFuture = $this->gt($other);

        $distanceInSeconds = $other->diffInSeconds($this);
        $distanceInMinutes = ceil($distanceInSeconds/60); 
        $options = [];
        
        if ($distanceInSeconds < 30) {
            $t = 'less_than_a_minute';
        } elseif ($distanceInSeconds < 90) {
            $t = 'one_minute';
        } elseif ($distanceInSeconds < 2670) {
            $t = 'x_minutes';
            $options = ['n' => $distanceInMinutes];
        } elseif ($distanceInSeconds < 5370) {
            $t = 'about_one_hour';
        } elseif ($distanceInSeconds < 86370) {
            $t = 'about_x_hours';
            $options = ['n' => ceil($distanceInMinutes/60)];
        } elseif ($distanceInSeconds < 151170) {
            $t = 'one_day';
        } elseif ($distanceInSeconds < 2591970) {
            $t = 'x_days';
            $options = ['n' => ceil(($distanceInMinutes/60)/24)];
        } elseif ($distanceInSeconds < 5183970) {
            $t = 'about_one_month';
        } elseif ($distanceInSeconds < 31536059) {
            $t = 'x_months';
            $options = ['n' => ceil((($distanceInMinutes/60)/24)/31)];
        } elseif ($distanceInSeconds < 39312001) {
            $t = 'about_one_year';
        } elseif ($distanceInSeconds < 54864001) {
            $t = 'over_a_year';
        } elseif ($distanceInSeconds < 31536001) {
            $t = 'almost_two_years';
        } else {
            $t = 'about_x_years';
            $options = ['n' => ceil($distanceInMinutes/60/24/365)];
        }
        
        $key = 'actionview.helper.date.';
        
        if ($isNow) {
            if ($isFuture) {
                $type = 'from_now';
            } else {
                $type = 'ago';
            }
        } elseif ($isFuture) {
            $type = 'after';
        } else {
            $type = 'before';
        }
        
        $tr = $this->getService('i18n');
        $key = 'activesupport.carbon.diff_for_humans.' . $type . '.' . $t;
        $tenseKey = 'activesupport.carbon.diff_for_humans.tenses.' . $type;
        
        if ($locale !== null) {
            $tense = $tr->t($tenseKey, array_merge($options, ['fallbacks' => false]), $locale);
            
            if (!$tense) {
                throw new Exception\RuntimeException(
                    sprintf(
                        "Couldn't find translation with specific locale '%s' for key '%s'",
                        $tenseKey
                    )
                );
            }
            
            if (is_string($tense)) {
                if ($tense) {
                    $options['t'] = $tense;
                }
            }
        } else {
            $locales = array_unique(array_merge([$tr->locale()], $tr->fallbacks()));
            
            $tenseData = $this->findTenseTranslation($tenseKey, $locales);
            if (!$tenseData) {
                throw new Exception\RuntimeException(
                    sprintf(
                        "Couldn't find translation for key '%s'",
                        $tenseKey
                    )
                );
            }
            
            list ($tense, $locale) = $tenseData;
            if ($tense) {
                $options['t'] = $tense;
            }
        }
        
        $diff = $tr->t($key, $options, $locale);
        
        if (!$diff) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Couldn't find translation for key '%s'",
                    $key
                )
            );
        }
        
        return $diff;
    }
    
    protected function findTenseTranslation($tenseKey, array $locales)
    {
        $tr = $this->getService('i18n');
        
        foreach ($locales as $locale) {
            $tense = $tr->t($tenseKey, ['fallbacks' => false], $locale);
            
            if (is_string($tense)) {
                return [$tense, $locale];
            }
        }
        
        return false;
    }
}
