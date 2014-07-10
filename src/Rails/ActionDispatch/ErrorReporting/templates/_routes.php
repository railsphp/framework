<table id='route_table' class='route_table'>
  <thead>
    <tr>
      <th>Helper</th>
      <th>HTTP Verb</th>
      <th>Path</th>
      <th>Controller#Action</th>
    </tr>
    <tr class='bottom'>
      <th>
        <a data-route-helper="_path" href="#" title="Returns a relative path (without the http or domain)">Path</a> /
        <a data-route-helper="_url" href="#" title="Returns an absolute url (with the http and domain)">Url</a>
      </th>
      <th>
      </th>
      <th>
        <input id="path_search" name="path[]" placeholder="Path Match" type="search" />
      </th>
      <th>
      </th>
    </tr>
  </thead>
  <tbody class='matched_paths' id='matched_paths'>
  </tbody>
  <tbody>
    <?php $this->setPresenter('Rails\ActionDispatch\ErrorReporting\RoutePresenter') ?>
    <?php foreach ($this->routes as $route) : ?>
    <?php $this->present($route) ?>
    <tr class='route_row' data-helper='path'>
      <td data-route-name='<?= $this->present()->name() ?>'>
          <?= $this->present()->name() ?><span class='helper'>Path</span>
      </td>
      <td data-route-verb='<?= $this->present()->verbs() ?>'>
        <?= $this->present()->name ?>
      </td>
      <td data-route-path='<?= $this->present()->path() ?>' data-regexp='<?= $this->present()->regexp() ?>'>
        <?= $this->present()->path() ?>
      </td>
      <td data-route-reqs='<?= $this->present()->endPoint() ?>'>
        <?= $this->present()->endPoint() ?>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<script type='text/javascript'>
  function each(elems, func) {
    if (!elems instanceof Array) { elems = [elems]; }
    for (var i = 0, len = elems.length; i < len; i++) {
      func(elems[i]);
    }
  }

  function setValOn(elems, val) {
    each(elems, function(elem) {
      elem.innerHTML = val;
    });
  }

  function onClick(elems, func) {
    each(elems, function(elem) {
      elem.onclick = func;
    });
  }

  // Enables functionality to toggle between `_path` and `_url` helper suffixes
  function setupRouteToggleHelperLinks() {
    var toggleLinks = document.querySelectorAll('#route_table [data-route-helper]');
    onClick(toggleLinks, function(){
      var helperTxt   = this.getAttribute("data-route-helper"),
          helperElems = document.querySelectorAll('[data-route-name] span.helper');
      setValOn(helperElems, helperTxt);
    });
  }

  // takes an array of elements with a data-regexp attribute and
  // passes their their parent <tr> into the callback function
  // if the regexp matchs a given path
  function eachElemsForPath(elems, path, func) {
    each(elems, function(e){
      var reg = e.getAttribute("data-regexp");
      if (path.match(RegExp(reg))) {
        func(e.parentNode.cloneNode(true));
      }
    })
  }

  // Ensure path always starts with a slash "/" and remove params or fragments
  function sanitizePath(path) {
    var path = path.charAt(0) == '/' ? path : "/" + path;
    return path.replace(/\#.*|\?.*/, '');
  }

  // Enables path search functionality
  function setupMatchPaths() {
    var regexpElems     = document.querySelectorAll('#route_table [data-regexp]'),
        pathElem        = document.querySelector('#path_search'),
        selectedSection = document.querySelector('#matched_paths'),
        noMatchText     = '<tr><th colspan="4">None</th></tr>';


    // Remove matches if no path is present
    pathElem.onblur = function(e) {
      if (pathElem.value === "") selectedSection.innerHTML = "";
    }

    // On key press perform a search for matching paths
    pathElem.onkeyup = function(e){
      var path        = sanitizePath(pathElem.value),
          defaultText = '<tr><th colspan="4">Paths Matching (' + path + '):</th></tr>';

      // Clear out results section
      selectedSection.innerHTML= defaultText;

      // Display matches if they exist
      eachElemsForPath(regexpElems, path, function(e){
        selectedSection.appendChild(e);
      });

      // If no match present, tell the user
      if (selectedSection.innerHTML === defaultText) {
        selectedSection.innerHTML = selectedSection.innerHTML + noMatchText;
      }
    }
  }

  setupMatchPaths();
  setupRouteToggleHelperLinks();
</script>
