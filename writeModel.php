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
 * writeModel.php 
 * 
 *
 * @author  Michael Gill
 * @version 180525
 *********************************************************************
 */
function writeValueObject($json, $tableName, $columns)
{
    //------------------------------------
    // configured database information
    //------------------------------------
    $dbname     = $json['dbname'];
    $dbserver   = $json['dbserver'];
    $dbport     = $json['dbport'];
    $dbdriver   = $json['dbdriver'];
    $dbuser     = $json['dbuser'];
    $dbpassword = $json['dbpassword'];
    $modeldir   = $json['modeldir'];
    $dbbaseclass= $json['dbbaseclass'];
    //--------------------------------------
    // the table name and user and version
    //--------------------------------------
    $TableName     = (ucFirst($tableName));
    $user          = getenv("USER");
    $version       = date("ymd");
    //--------------------------------------
    // create list of table attributes
    //--------------------------------------
    $fieldString ="\n"; 
    foreach($columns as $column)
    {
        if((strpos($column['Type'],"int(" )       === false) &&
           (strpos($column['Type'],"tinyint(" )   === false) &&
           (strpos($column['Type'],"smallint(" )  === false) &&
           (strpos($column['Type'],"mediumint(" ) === false) &&
           (strpos($column['Type'],"bigint(" )    === false)) 
        {
        $fieldString = $fieldString.
	"    public $".$column['Field']."=\"\";\n";
        }
	else
	{
        $fieldString = $fieldString.
	"    public $".$column['Field']."=0;\n";
        }
    }
    //-------------------------------------
    // create list of http parameters
    //-------------------------------------
    $fieldHttpString ="\n        \$b =\"&\";\n";
    foreach($columns as $column)
    {
        $fieldHttpString = $fieldHttpString.
	"        \$b.=\"".$column['Field']."=\".\$this->".$column['Field'].".\"&\";\n";
    }
    $fieldHttpString = $fieldHttpString.
    "        return(\$b);\n";
    //----------------------------------
    // create JSON constructor
    //----------------------------------
    $fieldJsonString ="\n";
    foreach($columns as $column)
    {
        $fieldJsonString = $fieldJsonString.
	"        \$this->".$column['Field']."= \$json['".$column['Field']."'];\n";
    }
    //----------------------------------
    // This is a very long string...
    //----------------------------------
    $valueObjectString = <<<EOF
<?php
require_once "DBObject.php";

/********************************************
 * $TableName represents a table in $dbname 
 *
 * @author  $user
 * @version $version
 ********************************************
 */
class $TableName extends DBObject
{    $fieldString


    /*****************************************************
     * Returns an HTTP parameter list for $TableName object
     *
     * @return
     *****************************************************
     */
    public function makeHTTPParameters()
    {    $fieldHttpString

    }

    /**************************************************************
     * Returns a JSON encoded representation of the $TableName object
     *
     * @return JSON
     **************************************************************
     */
    public function makeJSON()
    {
        return(json_encode(\$this));
    }

    /******************************************************
     * Construct a $TableName from a JSONObject.
     *
     * @param json
     *        A JSONObject.
     ******************************************************
     */
    function {$TableName}(\$jsonString='')
    {
        //--------------------------------------------------------------------
        // I'm basically OK with being quiet on missing JSON property names
        //--------------------------------------------------------------------
        error_reporting( error_reporting() & ~E_NOTICE );
        error_reporting( error_reporting() & ~E_WARNING );

        if(\$json = \$this->getJSON(\$jsonString) )
        {        $fieldJsonString
        }
    }
}

?>

EOF;
    echo "writing ".$modeldir.$TableName.".php...\n";
    file_put_contents($modeldir.$TableName.".php", $valueObjectString);
}

function writeObjectModel($json, $tableName, $columns)
{
    $dbname     = $json['dbname'];
    $dbserver   = $json['dbserver'];
    $dbport     = $json['dbport'];
    $dbdriver   = $json['dbdriver'];
    $dbuser     = $json['dbuser'];
    $dbpassword = $json['dbpassword'];
    $modeldir   = $json['modeldir'];
    $dbbaseclass= $json['dbbaseclass'];

    $TableName     = (ucFirst($tableName));
    $user          = getenv("USER");
    $version       = date("ymd");
    //-----------------------------------
    // Let's see if there's a primary key
    // we can depend on...
    //-----------------------------------
    $primaryKey="";
    $modelType ="";
    foreach($columns as $column)
    {
        if($column['Key']   == "PRI" &&
	       $column['Extra'] == "auto_increment")
        {
            $primaryKey = $column['Field'];
	        $modelType  = "table";
	    }
    }
    //-----------------------------------
    // if theres many primary keys
    // it's probably a many2many table
    // otherwise it's likely a view
    //-----------------------------------
    $prikeys=0;
    $m2mPriKey ="";
    $otherPriKey ="";
    foreach($columns as $column)
    {
        if($column['Key']   == "PRI")
            $prikeys++;
    }
    if(($prikeys > 1) && ($modelType != "table"))
    {
        $modelType="many2many";
        $m2mPriKey  = $columns[0]['Field'];
        $otherPriKey= $columns[1]['Field'];
    }
    elseif(($prikeys ==0) && ($modelType != "table"))
        $modelType="view";
    //----------------------------------
    // now, make the selectString...
    //----------------------------------
    $selectString ="";
    for($i=0; $i < count($columns); $i++)
    {
        $column = $columns[$i];
        if($column['Field'] != $primaryKey)
	{
            if($i != count($columns) -1)
	    {
                $selectString=$selectString.
	        "\"".$column['Field'].",\".\n".
		"                      ";
	    }
	    else
	    {
                $selectString=$selectString.
	        "\"".$column['Field']." \".".
		"                      ";
            }
	}
    }
    //-----------------------------------
    // if this is a many2many table
    // say primaryKey is first field...
    //----------------------------------
    //if($modelType == "many2many")
    //    $primaryKey = $columns[0]['Field'];
    //----------------------------------
    // now, make the valuesString...
    //----------------------------------
    $valuesString ="";
    for($i=0; $i < count($columns); $i++)
    {
        $column = $columns[$i];
        if((strpos($column['Type'],"int(" )       === false) &&
           (strpos($column['Type'],"tinyint(" )   === false) &&
           (strpos($column['Type'],"smallint(" )  === false) &&
           (strpos($column['Type'],"mediumint(" ) === false) &&
           (strpos($column['Type'],"bigint(" )    === false))
        {
	    $quote       ="'";
	    $start_slash ="\$this->sqlSafe(";
	    $end_slash   =")";
        }
        else
        {
            $quote       =" ";
	    $start_slash ="";
	    $end_slash   ="";
        }
        if($column['Field'] != $primaryKey)
	{
            if($i != count($columns) -1)
	    {
                $valuesString=$valuesString.
	        "\"".$quote."\".". $start_slash."\$".$tableName."->".$column['Field'].$end_slash.".\"".$quote.",\".\n".
                "                      ";
            }
	    else
	    {
                $valuesString=$valuesString.
	        "\"".$quote."\".". $start_slash."\$".$tableName."->".$column['Field'].$end_slash.".\"".$quote." \".".
		"                      ";
            }
	}
	else
	{
            $valuesString=$valuesString.
	    "\"null,\".\n".
            "                      ";
        }
    }
    //----------------------------------
    // now, make the updateString...
    //----------------------------------
    $updateString ="";
    for($i=0; $i < count($columns); $i++)
    {
        $column = $columns[$i];
        if((strpos($column['Type'],"int(" )       === false) &&
           (strpos($column['Type'],"tinyint(" )   === false) &&
           (strpos($column['Type'],"smallint(" )  === false) &&
           (strpos($column['Type'],"mediumint(" ) === false) &&
           (strpos($column['Type'],"bigint(" )    === false)) 
        {
           $quote        ="'";
	    $start_slash ="\$this->sqlSafe(";
	    $end_slash   =")";
        }
        else
	{
	    $quote       =" ";
	    $start_slash ="";
	    $end_slash   ="";
	}
        if($i != count($columns) -1)
	{
            $updateString=$updateString.
	    "\"".$column['Field']."=".$quote."\".".$start_slash."\$".$tableName."->".$column['Field'].$end_slash.".\"".$quote.",\".\n".
            "                      ";
        }
	else
	{
            $updateString=$updateString.
	    "\"".$column['Field']."=".$quote."\".".$start_slash."\$".$tableName."->".$column['Field'].$end_slash.".\"".$quote." \".".
	    "                      ";
        }
    }
    //----------------------------------
    // This is a very long string
    // for a "table" type of model
    //----------------------------------
    $objectModelString = <<<EOF
<?php
require_once "{$dbbaseclass}.php";
require      "{$TableName}.php";

/********************************************************************
 * {$TableName}Model inherits $dbbaseclass and provides functions to
 * map $TableName class to $dbname.
 *
 * @author  $user
 * @version $version
 *********************************************************************
 */
class {$TableName}Model extends $dbbaseclass
{
    /*********************************************************
     * Returns a $TableName by $primaryKey
     *
     * @return $tableName
     *********************************************************
     */
    public function find(\$id)
    {
        \$query="SELECT $primaryKey,".
                      $selectString		               
	       "FROM $tableName ".
	       "WHERE $primaryKey=".\$id;

        return(\$this->selectDB(\$query, "$TableName"));
    }

    /*********************************************************
     * Insert a new $TableName into $dbname database
     *
     * @param \${$tableName}
     * @return n/a
     *********************************************************
     */
    public function insert(\${$tableName})
    {
        \$query="INSERT INTO $tableName ( ".
	              "$primaryKey,".
                      $selectString
                           ")".
               "VALUES (".
                      $valuesString
                      ")"; 

        \$this->executeQuery(\$query);
    }


    /*********************************************************
     * Insert a new $TableName into $dbname database
     * and return a $TableName with new autoincrement
     * primary key
     *
     * @param  \${$tableName}
     * @return \${$tableName}
     *********************************************************
     */
    public function insert2(\${$tableName})
    {
        \$query="INSERT INTO $tableName ( ".
	              "$primaryKey,".
                      $selectString
                           ")".
               "VALUES (".
                      $valuesString
                      ")"; 

        \$id = \$this->executeInsert(\$query);
	    \${$tableName}->{$primaryKey} = \$id;
	    return(\${$tableName});	
    }


    /*********************************************************
     * Update a $TableName in $dbname database
     *
     * @param \${$tableName}
     * @return n/a
     *********************************************************
     */
    public function update(\${$tableName})
    {
        \$query="UPDATE  $tableName ".
	          "SET ".
                      $updateString
	          "WHERE $primaryKey=".\${$tableName}->{$primaryKey};

        \$this->executeQuery(\$query);
    }

    /*********************************************************
     * Delete a $TableName by $primaryKey
     *
     * @param  \$id
     * @return n/a
     *********************************************************
     */
    public function delete(\$id)
    {
        \$query="DELETE FROM $tableName WHERE $primaryKey=".\$id;

        \$this->executeQuery(\$query);
    }
}

?>
EOF;
    $objectModel2String = <<<EOF
<?php
require_once "{$dbbaseclass}.php";
require      "{$TableName}.php";

/********************************************************************
 * {$TableName}Model inherits $dbbaseclass and provides functions to
 * map $TableName class to $dbname.
 *
 * @author  $user
 * @version $version
 *********************************************************************
 */
class {$TableName}Model extends $dbbaseclass
{
    /*********************************************************
     * Insert a new $TableName into $dbname database
     *
     * @param \${$tableName}
     * @return n/a
     *********************************************************
     */
    public function insert(\${$tableName})
    {
        \$query="INSERT INTO $tableName ( ".
                      $selectString
                           ")".
               "VALUES (".
                      $valuesString
                     ")"; 

        \$this->executeQuery(\$query);
    }

    /*********************************************************
     * Delete a $TableName by keys
     *
     * @param  \$id
     * @param  \$id2
     *
     * @return n/a
     *********************************************************
     */
    public function delete(\$id, \$id2)
    {
        \$query="DELETE FROM $tableName WHERE $m2mPriKey=".\$id." AND $otherPriKey=".\$id2;

        \$this->executeQuery(\$query);
    }

}
?>    
EOF;

    $objectModel3String = <<<EOF
<?php
require_once "{$dbbaseclass}.php";
require      "{$TableName}.php";

/********************************************************************
 * {$TableName}Model inherits $dbbaseclass and provides the select() 
 * function which maps the $TableName class/VIEW in $dbname.
 *
 * @author  $user
 * @version $version
 *********************************************************************
 */
class {$TableName}Model extends $dbbaseclass
{
    /*********************************************************
     * Returns  $TableName VIEW
     *
     * @return $tableName
     *********************************************************
     */
    public function select()
    {
        \$query="SELECT ".
                      $selectString		               
	       "FROM $tableName ";
        return(\$this->selectDB(\$query, "$TableName"));
    }
}

?>
EOF;
    echo "writing ".$modeldir.$TableName."Model.php...\n";

    if($modelType == "table")
    {
        file_put_contents($modeldir.$TableName."Model.php", $objectModelString);
    }
    else if($modelType == "view")
    {
        file_put_contents($modeldir.$TableName."Model.php", $objectModel3String);
    }
    else
    {
        file_put_contents($modeldir.$TableName."Model.php", $objectModel2String);
    }
}

?>
