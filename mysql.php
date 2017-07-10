<?php

global $connection;
$connection = new mysqli('localhost', 'User', 'Password', 'DB');

if ($connection->connect_errno) {
	die("Falló la conexión con MySQL: (" . $connection->connect_errno . ") " . $connection->connect_error);
}
if(!$connection->set_charset("utf8")) {
	die("Error cargando el conjunto de caracteres utf8: " . $connection->error);
}

/**
 *
 */
function beginTransaction() {
	global $connection;

	$connection->autocommit(false);
}

/**
 *
 */
function commitTransaction() {
	global $connection;

	$connection->commit();
}

/**
 *
 */
function rollbackTransaction() {
	global $connection;

	$connection->rollback();
}

/**
 * Ejecuta un Query y devuelve el resultado para ser leido
 * 
 * @param query string
 * Es la consulta a ejecutar.
 * 
 * @return mysqli_result
 * Devuelve un objeto de resultado de mysqli. Recuerde al finalizar su uso ejecutar el método free.
 */
function executeQuery($query)
{
	global $connection;
	
	// Si fallo la ejecución de la Query devolvemos falso
	if(!$res = $connection->query($query))
	{
		return false;
	}

	// Devolvemos el resultado
	return $res;
}

function executeNonQuery($query)
{
	global $connection;
	
	// Ejecutamos la Query y comprobamos si vino o no un objecto mysqli_result
	if($res = $connection->query($query)) 
	{
		return true; 
	}
	else 
	{ 
		return false; 
	}
}

function executeScalar($query)
{
	global $connection;
	
	// Si fallo la ejecución de la Query devolvemos falso
	if(!$res = $connection->query($query))
	{
		return false;
	}
	
	// Nos paramos en la primera fila
	$res->data_seek(0);
	
	// Obtenemos la primera columna de la primera fila
	$ret = $res->fetch_row()[0];
	
	// Liberamos el resultado
	$res->free();
	
	// Devolvemos el resultado
	return $ret;
}

function executeScalarRow($query)
{
	global $connection;
	
	// Si fallo la ejecución de la Query devolvemos falso
	if(!$res = $connection->query($query))
	{
		return false;
	}

	// Nos paramos en la primera fila
	$res->data_seek(0);

	// Devolvemos la primera fila
	return $res->fetch_assoc();
}

function executeArray($query)
{
	global $connection;

	// Si fallo la ejecución de la Query devolvemos falso
	if(!$res = $connection->query($query))
	{
		return false;
	}

	$data = array();
	while ($row = $res->fetch_assoc()) {
		$data[] = $row;
	}

	return $data;
}

function executeInsertAutoNum($query)
{
	global $connection;

	// Si fallo la ejecución de la Query devolvemos falso
	if(!$res = $connection->query($query))
	{
        echo $query;
		return false;
	}

	// Nos paramos en la primera fila
	return $connection->insert_id;
}

function GetTableColumns($table)
{
	global $connection;

	// Si fallo la ejecución de la Query devolvemos falso
	if(!$res = $connection->query('SHOW COLUMNS FROM ' . $table))
	{
		return false;
	}

	// Obtenemos las columnas
	$cols = array();
	while ($row = $res->fetch_assoc()) {
		$cols[] = $row;
	}

	return $cols;
}

function executeInsertWithValues($table, $values)
{
	global $connection;

	$cols = GetTableColumns($table);

	$query = 'INSERT INTO `' . $table . '` ';
	$q_columns = '';
	$q_values = '';

	foreach($cols as $value) {
		$field = $value['Field'];
		$q_columns .= ', `' . $field . '`';

		if(isset($values[$field])) {
			$q_values .= ', \'' . $connection->real_escape_string($values[$field]) . '\'';
		} else {
			$q_values .= ', null';
		}
	}
	$q_columns = substr($q_columns, 2);
	$q_values = substr($q_values, 2);

	$query .= '(' . $q_columns . ') VALUES (' . $q_values . ');';

	return executeInsertAutoNum($query);
}

function executeUpdateWithValues($table, $pk_field, $values)
{
	global $connection;

	$query = 'UPDATE `' . $table . '` ';
	$q_values = '';
	$q_where = 'WHERE ' . $pk_field . ' = \'' . $values[$pk_field] . '\';';
	$cols = GetTableColumns($table);
	$old_values = executeScalarRow('SELECT * FROM ' . $table . ' ' . $q_where);
	$firstValue = true;

	$doUpdate = false;
	
	foreach($cols as $value) {
		$field = $value['Field'];

		if(isset($values[$field])) {
			if($values[$field] != $old_values[$field]) {
				$doUpdate = true;

				if($firstValue){
					$q_values .= ' SET `' . $field . '` = \'' . $connection->real_escape_string($values[$field]) . '\'';
					$firstValue = false;
				}
				else
					$q_values .= ', ' . $field . ' = \'' . $values[$field] . '\'';
			}
		}
	}

	$query .= $q_values . ' ' . $q_where;

	return $doUpdate ? executeNonQuery($query) : true;
}

function executeDeleteWithValues($table, $pk_form, $values, $pk_db = 'id')
{
	global $connection;

	$query = 'DELETE FROM ' . $table . ' WHERE ' . $pk_db . ' = \'' . $connection->real_escape_string($values[$pk_form]) . '\';';

	return executeNonQuery($query);
}

function executeLogicDeleteWithValues($table, $pk_form, $values, $pk_db = 'id')
{
	global $connection;

	$query = 'UPDATE ' . $table . ' SET deleted = 1 WHERE ' . $pk_db . ' = \'' . $connection->real_escape_string($values[$pk_form]) . '\';';

	return executeNonQuery($query);
}

?>
