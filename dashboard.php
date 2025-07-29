<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Cek Session</title>
</head>
<body>

<h2>Debug Session</h2>
<pre>
<?php
// Cetak seluruh isi $_SESSION
print_r($_SESSION);
?>
</pre>

<script>
  // Kirim session ke console (jika ingin)
  const sessionData = <?= json_encode($_SESSION) ?>;
  console.log("SESSION DATA:", sessionData);
</script>

</body>
</html>
