<?php $this->provide('pageTitle', 'Routes') ?>

<style>
.routes-container {
  width: 1170px;
  margin: 0 auto;
}
table.routes-table thead {
  border-bottom: 2px solid #bbb;
}
table.routes-table {
  border-collapse: collapse;
  width: 100%;
}
.routes tr:nth-child(odd) {
  background-color: #f9f9f9;
}
table.routes-table th {
  font-size: 14px;
}
table.routes-table th, table.routes-table td {
  padding: 8px;
}
table.routes-table tbody td {
  vertical-align: top;
  border-top: 1px solid #ddd;
  font-family:Courier New, sans-serif;
  font-size: 14px;
}
table.routes-table tbody td:first-child {
  text-align: right;
}
table.routes-table tbody td:nth-child(2) {
  text-align: center;
}
table.routes-table .matched_paths {
  background-color: #E2E4EF;
  border-bottom: solid 3px #8892BF;
}

#path_search {
  padding: 4px;
  width: 100%;
}
</style>

<div class="routes-container">
  <h1>Routes</h1>
  <p>Routes match in priority from top to bottom.</p>
  
  <input id="path_search" name="path[]" placeholder="Path Match" type="search" />
  
  <table id="route_table" class="routes-table">
    <thead>
      <th>Name</th>
      <th>HTTP Verb</th>
      <th>Path</th>
      <th>Controller#Action / EndPoint</th>
    </thead>
    
    <tbody class="matched_paths" id="matched_paths"></tbody>
    <br />
    <br />
    
    <tbody class="routes">
      <?php
      foreach ($this->routes as $route) :
          $this->presenter()->setObject($route);
      ?>
      <tr>
        <td><?= $route->name() ?></td>
        <td><?= $this->present('via') ?></td>
        <td data-regexp="<?= $this->present('pathRegex') ?>"><?= $route->path() ?></td>
        <td><?= $this->present('endPoint') ?></td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<?= $this->partial('route_scripts') ?>
