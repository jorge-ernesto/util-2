<?php

//Nos conectamos a postgres
$dbconn = pg_connect("host=127.0.0.1 user=postgres password=conejitalinda777 dbname=nuclear port=5432") or die('Could not connect: ' . pg_last_error());

//Obtenemos recurso imagen
$logo_file = 'pg_copy.png';
$logo_handle = fopen($logo_file, 'wb') or die('Cannot open file:  '.$logo_file);
$chunk_size = 10000;

//Comenzamos BEGIN transaccion de postgresql
pg_query($dbconn, "BEGIN") or die('BEGIN failed: ' . pg_last_error());

//Query para obtener OID de base de datos
$logo_name = '\'pg_logo\'';
$query = 'SELECT logo_img_oid FROM tab_logos WHERE logo_name = ' . $logo_name . ';';
$result = pg_query($query) or die('Select from logos table failed: ' .
pg_last_error());

//Obtenemos OID de base de datos
$row = pg_fetch_row($result);
$logo_oid = $row[0];
pg_free_result($result);
echo "<p>Logo OID : $logo_oid</p>";

//Leemos de BLOBs la imagen
$lo_handle = pg_lo_open($dbconn, $logo_oid, "r") or die('pg_lo_open failed: ' . pg_last_error());
echo "<p>LO Handle : $lo_handle</p>";

while (true) {
    //Obtenemos imagen en png para ser leida en img
    $logo_data = pg_lo_read($lo_handle, $chunk_size) or die('pg_lo_read failed: ' . pg_last_error());
    if ($logo_data === false)
        break;

    //Obtenemos tamaño de imagen
    $data_len = strlen($logo_data);
    echo "<p>Chunk Length: $data_len</p>";

    //Guardamos imagen en recurso de imagen
    fwrite($logo_handle, $logo_data, $data_len) or die('Cannot write to file:  '.$logo_file);
    $offset = pg_lo_tell($lo_handle);
    echo "<p>Seek position is: $offset</p>";

    //Verificamos integridad de la imagen
    if ($data_len < $chunk_size)
        break;
 }

//Cerramos lectura de archivos
fclose($logo_handle) or die('Cannot close file:  '.$logo_file);

//Cerramos BLOBs
pg_lo_close($lo_handle) or die('pg_lo_close failed: ' . pg_last_error());

//Finalizamos transaccion
pg_query($dbconn, "COMMIT;")  or die('COMMIT failed: ' . pg_last_error());

//Cerramos conexion
pg_close($dbconn);

//Mensaje success
echo "<p>Image file created by reading large object</p>";
