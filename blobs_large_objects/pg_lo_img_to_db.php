<?php

//Nos conectamos a postgres
$dbconn = pg_connect("host=127.0.0.1 user=postgres password=conejitalinda777 dbname=nuclear port=5432")or die('Could not connect: ' . pg_last_error());

//Obtenemos recurso imagen
$logo_file = 'pg.png';
$logo_handle = fopen($logo_file, 'rb') or die('Cannot open file:  ' . $logo_file);
$chunk_size = 10000;
echo "<p>Logo Resource : $logo_handle</p>";

//Comenzamos BEGIN transaccion de postgresql
pg_query($dbconn, "BEGIN") or die('BEGIN failed: ' . pg_last_error());

//Obtenemos OID conectandonos a funcionalidad para crear BLOBs
$logo_oid = pg_lo_create($dbconn);
echo "<p>Logo OID : $logo_oid</p>";

//Obtenemos recurso BLOB, el cual vamos a setear o reescribir
$lo_handle = pg_lo_open($dbconn, $logo_oid, "w") or die('pg_lo_open failed: ' . pg_last_error());
echo "<p>LO Handle : $lo_handle</p>";
$logo_name = '\'pg_logo\'';

//Insertamos imagen
$query = 'INSERT INTO tab_logos(logo_name, logo_img_oid) VALUES(' . $logo_name . ',' . $logo_oid . ')';
$result = pg_query($query) or die('Insert to logos table failed: ' . pg_last_error());
pg_free_result($result);
echo "<p>Insert to table done</p>";
echo $query;

while (true) {
    //Obtenemos imagen en png para ser leida en img
    $logo_data = fread($logo_handle, $chunk_size) or die('Cannot read file:  ' . $logo_file); //$contents = fread($handle, filesize($filename));
    if ($logo_data === false) {
        break;
    }
    // echo $logo_data;    

    //Obtenemos tamaño de imagen
    $data_len = strlen($logo_data);
    echo "<p>Chunk Length: $data_len</p>";

    //Guardamos imagen en Storage de Postgres
    pg_lo_write($lo_handle, $logo_data, $data_len) or die('pg_lo_write failed: ' . pg_last_error());
    $offset = pg_lo_tell($lo_handle);
    echo "<p>Seek position is: $offset</p>";

    //Verificamos integridad de la imagen
    if ($data_len < $chunk_size) {
        break;
    }    
}

//Cerramos lectura de archivos
fclose($logo_handle) or die('Cannot close file:  ' . $logo_file);

//Cerramos BLOBs
pg_lo_close($lo_handle) or die('pg_lo_close failed: ' . pg_last_error());

//Finalizamos transaccion
pg_query($dbconn, "COMMIT;") or die('COMMIT failed: ' . pg_last_error());

//Cerramos conexion
pg_close($dbconn);

//Mensaje success
echo "<p>Image inserted into large object</p>";
