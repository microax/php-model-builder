<?php
/*
 Copyright (c) 2018 EntityObjects.com

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in all
 copies or substantial portions of the Software.

 The Software shall be used for Good, not Evil.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 SOFTWARE.
 */

/********************************************************************
 * writeDB.php 
 * 
 *
 * @author  Michael Gill
 * @version 180525
 *********************************************************************
 */
function writeDBClass($json)
{

    $dbname     = $json['dbname'];
    $dbserver   = $json['dbserver'];
    $dbport     = $json['dbport'];
    $dbdriver   = $json['dbdriver'];
    $dbuser     = $json['dbuser'];
    $dbpassword = $json['dbpassword'];
    $modeldir   = $json['modeldir'];
    $dbbaseclass= $json['dbbaseclass'];

    $user          = getenv("USER");
    $version       = date("ymd");
    //----------------------------------
    // This is a very long string...
    //----------------------------------
    $xxxDBstr = <<<EOF

<?php
require_once "DBConstants.php";

/********************************************************************
 * $dbbaseclass is the base class for $dbname access.
 *
 * @author  $user
 * @version $version
 ********************************************************************
 */
class $dbbaseclass implements DBConstants
{
    public \$conn = null;    //our DB connection

    /********************************************************
     * Returns a connection object for a $dbname connection
     *
     * @return \$conn
     *********************************************************
     */
    public function connectDB()
    {
        //--------------------------------------
	    // Construct Data Source Name (DSN)
	    // username and password for PDO object
	    //--------------------------------------
        \$dsn =""        .{$dbbaseclass}::DBDRIVER.  ":";
        \$dsn.="host="   .{$dbbaseclass}::DBSERVER.  ";";
        \$dsn.="dbname=" .{$dbbaseclass}::DBNAME.    ";";
        \$dsn.="port="   .{$dbbaseclass}::DBPORT.    ";";
        \$dsn.="charset=".{$dbbaseclass}::DBCHARSET;
	    \$username       ={$dbbaseclass}::DBUSER;
	    \$password       ={$dbbaseclass}::DBPASSWORD;

        return(new PDO(\$dsn,\$username,\$password));
    }

    /********************************************************
     * Returns an array of resulting rows for an objecct
     *
     * @param \$query
     * @param \$class
     *
     * @return \$results
     *********************************************************
     */
    public function selectDB(\$query, \$class )
    {
        \$this->conn = \$this->connectDB();

        \$result = \$this->conn->query(\$query);
	    \$result->setFetchMode(PDO::FETCH_CLASS, \$class);

        for(\$i=0; \$f=\$result->fetch(); \$i++)
	    if(\$f !=0)
	        \$r[\$i] = \$f;
		
        \$this->conn = null;
        return(\$r);
    }


    /********************************************************
     * executeQuery
     *
     * @param \$query
     * @return n/a
     *********************************************************
     */
    public function executeQuery(\$query)
    {
        \$this->conn = \$this->connectDB();
        \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        \$this->conn->exec(\$query);
        \$this->conn = null;
    }


    /********************************************************
     * executeInsert
     *
     * @param  \$query
     * @return \$id
     *********************************************************
     */
    public function executeInsert(\$query)
    {
        \$this->conn = \$this->connectDB();
        \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        \$this->conn->exec(\$query);
        \$id   = \$this->conn->lastInsertId();
        \$this->conn = null;
	    return(\$id);
    }

    /********************************************************
     * sqlSafe -- 100% sql injection proof sanitizer
     *            USE UTF8 CHARSET!
     *
     * @param  \$attr
     * @return \$attr
     *********************************************************
     */
    public function sqlSafe(\$attr)
    {
	    return(addslashes(\$attr));
    }
}

?>
EOF;
    echo "writing ".$modeldir.$dbbaseclass.".php...\n";
    file_put_contents($modeldir.$dbbaseclass.".php", $xxxDBstr);
}

function writeDBConstants($json)
{
    $dbname     = $json['dbname'];
    $dbserver   = $json['dbserver'];
    $dbport     = $json['dbport'];
    $dbdriver   = $json['dbdriver'];
    $dbuser     = $json['dbuser'];
    $dbpassword = $json['dbpassword'];
    $modeldir   = $json['modeldir'];
    $dbbaseclass= $json['dbbaseclass'];
    $dbcharset  = $json['dbcharset'];

    $user          = getenv("USER");
    $version       = date("ymd");
    //----------------------------------
    // This is a very long string...
    //----------------------------------
    $DBConststr = <<<EOF
<?php 

/*************************************************
 * interface defines common constants
 *
 * @author  $user
 * @version $version
 *************************************************
 */
interface DBConstants 
{
    //---------------------------------------------
    // $dbname parameters for PDO
    //---------------------------------------------
    const DBNAME    = "$dbname";
    const DBSERVER  = "$dbserver";
    const DBPORT    = "$dbport";
    const DBUSER    = "$dbuser";
    const DBPASSWORD= "$dbpassword";
    const DBDRIVER  = "$dbdriver";
    const DBCHARSET = "$dbcharset";

    //---------------------------------------------
    // JSON Response Codes
    //---------------------------------------------
    const RESPONSE_OK             = 200;
    const RESPONSE_UNAUTHORIZED   = 401;
    const RESPONSE_NOTFOUND       = 404;
    const RESPONSE_NOACCESS       = 422;
    const RESPONSE_SERVER_ERROR   = 500;
}

?>

EOF;
    echo "writing ".$modeldir."DBConstants.php...\n";
    file_put_contents($modeldir."DBConstants.php", $DBConststr);
}

function writeDBObject($json)
{
    $modeldir = $json['modeldir'];
    $user     = getenv("USER");
    $version  = date("ymd");
    //----------------------------------
    // This is a very long string...
    //----------------------------------
    $DBObjectstr = <<<EOF
<?php
require_once "DBConstants.php";

/********************************************************************
 * DBObject is the base class for Database Objects
 * This class contains error and status codes for DBObjects.
 *
 * @author  $user
 * @version $version
 ********************************************************************
 */
class DBObject implements DBConstants  
{

    protected \$dbErrors  = false;
    protected \$errorString ="";
    protected \$responseCode=0;
    protected \$notifyFlag  = false;

    public function DBObject()
    {

    }

  /******************************************************************
   * returns true if DBObject has errors in being constructed 
   *
   * @return  boolean -- true or false
   ******************************************************************
   */
    public function hasErrors()
    {
	return(\$this->dbErrors);
    }

  /**********************************************************************
   * returns response code from server processing a JSON request
   * and contructing this object
   *
   * @return  int response code
   **********************************************************************
   */
    public function getResponseCode()
    {
        return(\$this->responseCode);
    }

  /**********************************************************************
   * returns error string from server processing a JSON request
   * and contructing this object
   *
   * @return  String - describing errors
   **********************************************************************
   */
    public function getErrorString()
    {
	return(\$this->errorString);
    }


  /******************************************************************
   * Sets the error flag for this object
   *
   * @param  flag - boolean error flag
   ******************************************************************
   */
    public function setErrors(\$flag)
    {
	\$this->dbErrors = \$flag;
    }

  /*****************************************************************
   * Sets the Server Response code for this object
   *
   * @param code -- server response code
   *****************************************************************
   */
    public function setResponseCode(\$code)
    {
	\$this->responseCode = \$code;
    }

  /*****************************************************************
   * Sets the Server Error String for this object
   *
   * @param err -- String describing error
   *****************************************************************
   */
    public function setErrorString(\$err)
    {
	\$this->errorString = \$err;
    }

  /**************************************************************************
   * checks to see if json response has error codes
   * then returns a \$json object if valid or null if not
   *
   * @param  json string 
   * @return json object or null
   ***************************************************************************
   */
    public function getJSON(\$j)
    {
        \$r = null;
        \$this->setResponseCode(DBObject::RESPONSE_OK);

        if(\$j !="")
	{
	    \$r = json_decode(\$j, TRUE);
            if(json_last_error() != JSON_ERROR_NONE)
	    {
    	        \$this->setErrors(true);
	        \$this->setErrorString(json_last_error_msg());
            }
	    else
	    {
                if(isset(\$r['status']) && isset(\$r['error']))
		{
	            \$this->setResponseCode(\$r['status'] );
                    \$this->setErrorString(\$r['error']);
                    \$this->setErrors(true);
                    \$r = null;
                }
            }
        }
        else
        {
    	    \$this->setErrors(false);
	    \$this->setErrorString("OK");
	}

        return(\$r);
    }

  /**
   * Sets the notify flag on for debugging 
   *
   */
    public function isDebug()
    {
      return(\$this->notifyFlag);
    }
  /**
   * Sets the notify flag on for debugging 
   *
   */
    public function setDebug()
    {
	\$this->notifyFlag = true;
    }
  /**
   * ReSets the notify flag OFF for debugging 
   *
   */
    public function resetDebug()
    {
	\$this->notifyFlag = false;
    }
}

?>

EOF;
    echo "writing ".$modeldir."DBObject.php...\n";
    file_put_contents($modeldir."DBObject.php", $DBObjectstr);
}

?>
