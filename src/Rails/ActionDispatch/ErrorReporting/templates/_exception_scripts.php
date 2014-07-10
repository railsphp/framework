<script>
var toggle = function(id) {
  var s = document.getElementById(id).style;
  s.display = s.display == 'none' ? 'block' : 'none';
  return false;
}
var show = function(id) {
  document.getElementById(id).style.display = 'block';
}
var hide = function(id) {
  document.getElementById(id).style.display = 'none';
}
var toggleErrorContext = function() {
  return toggle('error_context');
}
</script>

