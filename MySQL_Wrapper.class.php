<?php
/**
 * @desc A wrapper which uses the standard MySQL API.
 * @author Guillaume Ch. (@cGuille) <cguille.dev@gmail.com>
 */
class MySQL_Wrapper {
    private $mysql_id;
    private $options = array();
    private $last_query = '';
    private $last_error = '';
    
    const OPT_ERRMODE       = 100;
    const ERRMODE_EXCEPTION = 101;
    const ERRMODE_FATAL     = 102;
    const ERRMODE_WARNING   = 103;
    
    const OPT_FETCHMODE     = 200;
    const FETCHMODE_ASSOC   = 201;
    const FETCHMODE_NUM     = 202;
    const FETCHMODE_OBJECT  = 203;
    
    /**
     * @desc Sets up a database connection.
     * @param string $db
     * @param string $host
     * @param string $user
     * @param string $password
     * @param array $options
     * @param string $port
     * @return void
     */
    public function __construct($db = '', $host = 'localhost', $user = 'root', $password = '', $options = array(), $port = '3306') {
        array_walk($options, array($this, 'setOption'));

        if(FALSE === $this->getOption(self::OPT_ERRMODE))
            $this->setOption(self::OPT_ERRMODE, self::ERRMODE_EXCEPTION);

        $this->mysql_id = @mysql_connect("$host:$port", $user, $password)
            or $this->error('Unable to connect to the SQL server : '. lcfirst(mysql_error()), mysql_errno());

        if (!empty($db)) {
            mysql_select_db($db, $this->mysql_id)
                or $this->error('Unable to use the specified database : '. lcfirst(mysql_error($this->mysql_id)), mysql_errno($this->mysql_id));
        }

        $this->initialize();
    }
    
    /**
     * @desc This method is called by the constructor. Here you can write the queries you want to be executed every time 
     *       you instantiate a connection. 
     * @return void
     */
    private function initialize() {
        $this->query("SET lc_time_names = 'fr_FR'");
        $this->query("SET NAMES UTF8;");
    }
    
    /**
     * @desc Send a query to the SGBDR. Use the markers :int, :dec, :str, :noq and :blo to specify the types of the params.
     *       Params given with :str and :noq markers are escaped as strings. Returns FALSE on error, an array in the case 
     *       of a SELECT or SHOW query and the number of affected rows in other cases.
     * @param string $query
     * @param mixed $param1, ...
     * @return mixed (array | int | FALSE)
     */
    public function query() {
        $args = func_get_args();
        if(empty($args)) {
            $this->error('The SQL query is empty');
            return FALSE;
        } else {
            $query = array_shift($args);
            $nb_args = count($args);
            $nb_markers =   substr_count($query, ':int') +
                            substr_count($query, ':dec') +
                            substr_count($query, ':str') +
                            substr_count($query, ':noq') +
                            substr_count($query, ':blo');
        }

        if($nb_args < 1) {
            if($nb_markers > 0) {
                $this->error("You had not given the params");
                return FALSE;
            }
        } else {
            if($nb_markers != $nb_args) {
                $this->error("Number of markers and params do not match (markers : $nb_markers, params : $nb_args)");
                return FALSE;
            }
            $query = $this->buildQuery($query, $args);
        }

        $this->last_query = $query;
        $stmt = mysql_query($this->last_query, $this->mysql_id);

        if (FALSE === $stmt) {
            $res = FALSE;
            $this->error(mysql_error($this->mysql_id), mysql_errno($this->mysql_id), $this->last_query);
        } elseif(TRUE === $stmt) {
            $res = mysql_affected_rows($this->mysql_id);
        } else {
            switch($this->getOption(self::OPT_FETCHMODE)) {
                case self::FETCHMODE_NUM :
                    $res = array();
                    while (FALSE !== (mysql_fetch_array($stmt, MYSQL_NUM))) {
                        $res[] = $row;
                    }
                    break;
                    
                case self::FETCHMODE_OBJECT :
                    $res = array();
                    while (FALSE !== ($row = mysql_fetch_assoc($stmt))) {
                        $res[] = (object) $row;
                    }
                    break;
                    
                case self::FETCHMODE_ASSOC :
                default :
                    $res = array();
                    while (FALSE !== ($row = mysql_fetch_assoc($stmt))) {
                        $res[] = $row;
                    }
                    break;
            }
        }
        return $res;
    }
    
    /**
     * @desc Returns the last auto-increment generated.
     * @return int
     */
    public function getInsertedAI() {
        return mysql_insert_id($this->mysql_id);
    }
    
    /**
     * @desc Returns the last error raised.
     * @return string
     */
    public function getLastError() {
        return $this->last_error;
    }

    /**
     * @desc Returns the last query sent.
     * @return string
     */
    public function getLastQuery() {
        return $this->last_query;
    }
    
    /**
     * @desc Set an attribute of the MySQL_Wrapper object options.
     * @param string $option
     * @param string $value
     * @return bool
     */
    public function setOption($option, $value) {
        $OPTIONS = array(
            self::OPT_ERRMODE => array(self::ERRMODE_EXCEPTION,self::ERRMODE_FATAL,self::ERRMODE_WARNING),
            self::OPT_FETCHMODE => array(self::FETCHMODE_ASSOC,self::FETCHMODE_NUM,self::FETCHMODE_OBJECT),
        );

        if(!in_array($option, array_keys($OPTIONS))) {
            $res = FALSE;
            $this->error("Unable to set the options : attribute '$option' does not exist");
        } elseif(!in_array($value, $OPTIONS[$option])) {
            $res = FALSE;
            $this->error("Unable to set the options : value '$value' for attribute '$option' does not exist");
        } else {
            $this->options[$option] = $value;
            $res = TRUE;
        }

        return $res;
    }
    
    /**
     * @desc Returns the $attr option's value.
     * @param string $attr
     * @return mixed
     */
    public function getOption($attr) {
        return isset($this->options[$attr]) ? $this->options[$attr] : FALSE;
    }


    private function buildQuery($query, $args) {
        for ($i=0, $m = count($args); $i < $m; $i += 1) {
            $pos = array();
            if (FALSE !== ($rv = strpos($query, ':int'))) {
                $pos[] = $rv;
            }
            if (FALSE !== ($rv = strpos($query, ':dec'))) {
                $pos[] = $rv;
            }
            if (FALSE !== ($rv = strpos($query, ':str'))) {
                $pos[] = $rv;
            }
            if (FALSE !== ($rv = strpos($query, ':noq'))) {
                $pos[] = $rv;
            }
            if (FALSE !== ($rv = strpos($query, ':blo'))) {
                $pos[] = $rv;
            }
            $pos = min($pos);
            $marker = substr($query, $pos, 4);
            $query = substr_replace($query, '%s', $pos, 4);

            switch($marker) {
                case ':int':
                    if(!is_int($args[$i])) {
                        $this->error("Wrong type for the param n°$i (int expected)");
                        return FALSE;
                    }
                    break;
                case ':dec':
                    if(!is_float($args[$i])) {
                        $this->error("Wrong type for the param n°$i (float expected)");
                        return FALSE;
                    }
                    break;
                case ':str':
                    if(!is_string($args[$i])) {
                        $this->error("Wrong type for the param n°$i (str expected)");
                        return FALSE;
                    }
                    $args[$i] = "'". mysql_real_escape_string($args[$i], $this->mysql_id) ."'";
                    break;
                case ':noq':
                    $args[$i] = mysql_real_escape_string($args[$i], $this->mysql_id);
                    break;
                case ':blo':
                    $args[$i] = "'". mysql_real_escape_string($args[$i], $this->mysql_id) ."'";
                    break;
                default:
                    $this->error("Invalid query marker : '$marker''");
                    return FALSE;
                    break;
            }
        }

        return vsprintf($query, $args);
    }
    
    private function error($message = '', $code = 0, $query = null) {            
        $this->last_error = $message .'[CODE='. $code .']';
        
        switch($this->getOption(self::OPT_ERRMODE)) {
            case self::ERRMODE_FATAL :
                trigger_error($this->last_error, E_USER_ERROR);
                break;
            case self::ERRMODE_WARNING :
                trigger_error($this->last_error, E_USER_WARNING);
                break;
            case self::ERRMODE_EXCEPTION :
            default :
                throw new MySQLException($this->last_error, $code, $query);
                break;
        }
    }
    
    public function __destruct() {
        mysql_close($this->mysql_id) or $this->error(mysql_error($this->mysql_id), mysql_errno($this->mysql_id));
    }
}


class MySQLException extends Exception {
    private $query;

    public function __construct($message, $code, $query) {
        parent::__construct($message, $code);
        $this->query = $query;
    }

    public function getQuery() {
        return $this->query;
    }
}