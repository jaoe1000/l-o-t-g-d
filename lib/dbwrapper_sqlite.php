<?php
// addnews ready
// translator ready
// mail ready
// modified by jaoe1000

if (!defined('SQLITE3_ASSOC')) {
    define('SQLITE3_ASSOC', 1);
}

/**
 * Execute a SQLite query.
 * @return void
 */
function db_query($sql = '', $params = [], $die = true)
{
    global $session, $dbinfo, $sqlite_resource;
    // Handle backward compatibility for signature: db_query($sql, $die)
    if (is_bool($params)) {
        $die = $params;
        $params = [];
    }
    if (!is_array($params)) {
        $params = [$params];
    }
    if (defined("DB_NODB") && !defined("LINK") && !is_object($sqlite_resource)) {
        return [];
    }
    $dbinfo['queriesthishit']++;
    $starttime = getmicrotime();

    if (!empty($params) && is_object($sqlite_resource)) {
        $stmt = $sqlite_resource->prepare($sql);
        if ($stmt === false) {
            // Parameter substitution fallback if prepare fails
            $offset = 0;
            foreach ($params as $param) {
                $pos = strpos($sql, '?', $offset);
                if ($pos === false) break;
                if ($param === null) {
                    $rep = 'NULL';
                } elseif (is_int($param) || is_float($param)) {
                    $rep = (string)$param;
                } elseif (is_bool($param)) {
                    $rep = $param ? '1' : '0';
                } else {
                    $rep = "'" . $sqlite_resource->escapeString((string)$param) . "'";
                }
                $sql = substr_replace($sql, $rep, $pos, 1);
                $offset = $pos + strlen($rep);
            }
            if (defined("IS_INSTALLER") && IS_INSTALLER) {
                $r = @$sqlite_resource->query($sql);
            } else {
                $r = $sqlite_resource->query($sql);
            }
        } else {
            $i = 1;
            foreach ($params as $val) {
                if (is_int($val)) {
                    $stmt->bindValue($i, $val, SQLITE3_INTEGER);
                } elseif (is_float($val)) {
                    $stmt->bindValue($i, $val, SQLITE3_FLOAT);
                } elseif (is_null($val)) {
                    $stmt->bindValue($i, null, SQLITE3_NULL);
                } else {
                    $stmt->bindValue($i, (string)$val, SQLITE3_TEXT);
                }
                $i++;
            }
            $r = $stmt->execute();
        }
    } else {
        if (defined("IS_INSTALLER") && IS_INSTALLER) {
            $r = @$sqlite_resource->query($sql);
        }
        else {
            $r = $sqlite_resource->query($sql);
        }
    }
    if (!$r && $die === true) {
        if (defined("IS_INSTALLER") && IS_INSTALLER) {
            return [];
        }
        else {
            if ((getSessionSuperUser() & SU_DEVELOPER)) {
                require_once("lib/show_backtrace.php");
                die(
                    "<pre>".
                    HTMLEntities(
                        $sql,
                        ENT_COMPAT,
                        getsetting("charset", "ISO-8859-1")
                    ).
                    "</pre>".
                    db_error(LINK).
                    show_backtrace()
                );
            }
            else {
                die(
                    "A most bogus error has occurred.  I apologise, but the page you were trying to access is broken.  Please use your browser's back button and try again."
                );
            }
        }
    }
    $endtime = getmicrotime();
    if ($endtime - $starttime >= 1.00 && ($session['user']['superuser'] & SU_DEBUG_OUTPUT)) {
        $s = trim($sql);
        if (strlen($s) > 800) {
            $s = substr($s,0,400)." ... ".substr($s,strlen($s)-400);
        }
        debug(
            "Slow Query (".
            round($endtime-$starttime,2).
            "s): ".
            HTMLEntities($s, ENT_COMPAT, getsetting("charset", "ISO-8859-1")).
            "`n"
        );
    }
    unset($dbinfo['affected_rows']);
    $dbinfo['affected_rows'] = db_affected_rows();
    $dbinfo['querytime'] += $endtime-$starttime;
    return $r;
}

/**
 * Execute a command and cache the results.
 * @return array
 */
function db_query_cached(string $sql, string $name, int $duration=900): array
{
    global $dbinfo;
    $data = datacache($name, $duration);
    if (is_array($data)){
        reset($data);
        $dbinfo['affected_rows']=-1;
        return $data;
    }
    else {
        $result = db_query($sql);
        $data = array();
        while ($row = db_fetch_assoc($result)) {
            $data[] = $row;
        }
        updatedatacache($name, $data);
        reset($data);
        return $data;
    }
}

/**
 * Grab the most recent error message.
 * @return string
 */
function db_error(): string
{
    global $sqlite_resource;
    $err = $sqlite_resource->lastErrorMsg();
    if (defined("DB_NODB") && !defined("DB_INSTALLER_STAGE4")) {
        return "The database connection was never established";
    }
    else if ($err == 'not an error') {
        return '';
    }
    else {
        return $err;
    }
}

/**
 * Return an associative array of recent statement, or checks the cache.
 * @return array|false
 */
function db_fetch_assoc(&$result)
{
    if (is_array($result)) {
        $val = array_shift($result);
        return $val !== null ? $val : false;
    }
    if ($result instanceof SQLite3Result || (is_object($result) && method_exists($result, 'fetchArray'))) {
        $mode = defined('SQLITE3_ASSOC') ? SQLITE3_ASSOC : 1;
        return $result->fetchArray($mode) ?: false;
    }
    return false;
}

/**
 * Get the ID of most recent insert.
 * @return int
 */
function db_insert_id(): int
{
    global $sqlite_resource;
    if (defined("DB_NODB") && !defined("LINK")){
        return -1;
    }
    else {
        return $sqlite_resource->lastInsertRowID();
    }
}

/**
 * Count the number of rows in a query result.
 * @return int
 */
function db_num_rows($result): int
{
    if (is_array($result)) {
        return count($result);
    }
    if ($result instanceof SQLite3Result || (is_object($result) && method_exists($result, 'fetchArray'))) {
        $rows = 0;
        if (method_exists($result, 'reset')) {
            $result->reset();
        }
        while ($result->fetchArray()) {
            $rows++;
        }
        if (method_exists($result, 'reset')) {
            $result->reset();
        }
        return $rows;
    }
    return 0;
}

/**
 * Count the number of recent changes in the database.
 * @return int
 */
function db_affected_rows(): int{
    global $dbinfo, $sqlite_resource;
    if (isset($dbinfo['affected_rows'])) {
        return $dbinfo['affected_rows'];
    }
    if (defined("DB_NODB") && !defined("LINK")) {
        return 0;
    }
    else {
        return $sqlite_resource->changes();
    }
}

/**
 * Connect to the SQLite database file.
 * @return bool
 */
function db_connect($host, string $user, string $pass): SQLite3
{
    global $sqlite_resource, $DB_NAME;
    $database = ($DB_NAME ? "$DB_NAME.sqlite" : "LotGD.sqlite");
    $sqlite_resource = New SQLite3($database, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
    //$sqlite_resource->query("CREATE TABLE logd_environment_test (a int(11) not null);");
    //var_dump($sqlite_resource->changes());
    return $sqlite_resource;
}

/**
 * Grab the server version data.
 * @return string The version number of SQLite3.
 */
function db_get_server_version(): string
{
    global $sqlite_resource;
    return $sqlite_resource->version()['versionString'];
}

/**
 * Check if a table exists.
 * @return bool
 */
function db_select_db(string $dbName): bool
{
    global $sqlite_resource;
    $dbName = addslashes($dbName);
    return is_object($sqlite_resource->query(
        "SELECT name FROM sqlite_master
        WHERE type='table' AND name='$dbName'"
    ));
}

/**
 * Free the most recent result.
 * @return bool
 */
function db_free_result($result): bool
{
    if (is_array($result)){
        unset($result);
        return true;
    }
    else{
        if (defined("DB_NODB") && !defined("LINK")) {
            return false;
        }
        else {
            return $result->finalize();
        }
    }
}

/**
 * Check if current table exists.
 * @return bool
 */
function db_table_exists(string $tableName): bool
{
    global $sqlite_resource;
    if (defined("DB_NODB") && !defined("LINK")) {
        return false;
    }
    return $exists = $sqlite_resource->query(
        sprintf("SELECT 1 FROM %s LIMIT 0", db_prefix($tableName))
    );
}

/**
 * Apply our prefix for tables to the table name.
 * @return string The prefixed name of specified table.
 */
function db_prefix(string $tableName): string
{
    global $DB_PREFIX;
    return $DB_PREFIX . $tableName;
}

function db_escape($string) {
    global $sqlite_resource;
    if (!$sqlite_resource) return addslashes($string);
    return $sqlite_resource->escapeString($string);
}
?>