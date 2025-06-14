<?php

declare(strict_types=1);

class C_PatientFinder extends Controller
{
    public $template_mod;

    public function __construct($template_mod = "general")
    {
        parent::__construct();
        $this->template_mod = $template_mod;
        $this->assign("FORM_ACTION", $GLOBALS['webroot'] . "/controller.php?" . attr($_SERVER['QUERY_STRING']));
        ///////////////////////////////////
        //// What should this be?????
        //////////////////////////////////
        $this->assign("CURRENT_ACTION", $GLOBALS['webroot'] . "/controller.php?" . "practice_settings&patient_finder&");
        /////////////////////////////////
        $this->assign("STYLE", $GLOBALS['style']);
    }

    public function default_action($form_id = '', $form_name = '', $pid = '')
    {
        return $this->find_action($form_id, $form_name, $pid);
    }

    /**
    * Function that will display a patient finder widget, allowing
    *   the user to input search parameters to find a patient id.
    */
    public function find_action($form_id, $form_name, $pid = null)
    {
        $isPid = false;

        $this->assign('form_id', $form_id);
        $this->assign('form_name', $form_name);
        if (!empty($pid)) {
            $isPid = true;
        }

        $this->assign('hidden_ispid', $isPid);

        return $this->fetch($GLOBALS['template_dir'] . "patient_finder/" . $this->template_mod . "_find.html");
    }

    /**
    * Function that will take a search string, parse it out and return all patients from the db matching.
    * @param string $search_string - String from html form giving us our search parameters
    */
    public function find_action_process(): void
    {

        if ($_POST['process'] != "true") {
            return;
        }

        $isPub = false;
        $search_string = $_POST['searchstring'];
        if (!empty($_POST['pid'])) {
            $isPub = !$_POST['pid'];
        }

        //get the db connection and pass it to the helper functions
        $sql = "SELECT CONCAT(lname, ' ', fname, ' ', mname) as name, DOB, pubpid, pid FROM patient_data";
        //parse search_string to determine what type of search we have
        $pos = strpos($search_string, ',');

        // get result set into array and pass to array
        $result_array = array();

        if ($pos === false) {
            //no comma just last name
            $result_array = $this->search_by_lName($sql, $search_string);
        } elseif ($pos === 0) {
            //first name only
            $result_array = $this->search_by_fName($sql, $search_string);
        } else {
            //last and first at least
            $result_array = $this->search_by_FullName($sql, $search_string);
        }

        $this->assign('search_string', $search_string);
        $this->assign('result_set', $result_array);
        $this->assign('ispub', $isPub);
        // we're done
        $_POST['process'] = "";
    }

    /**
     *   Function that returns an array containing the
     *   Results of a LastName search
     *   @-param string $sql base sql query
     *   @-param string $search_string parsed for last name
     * @return list<mixed>
     */
    public function search_by_lName(string $sql, $search_string): array
    {
        $lName = add_escape_custom($search_string);
        $sql .= sprintf(" WHERE lname LIKE '%s%%' ORDER BY lname, fname", $lName);
        $recordset = sqlStatement($sql);

        $result_array = [];
        while ($result = sqlFetchArray($recordset)) {
            $result_array[] = $result;
        }

        return $result_array;
    }

    /**
     *   Function that returns an array containing the
     *   Results of a FirstName search
     *   @param string $sql base sql query
     *   @param string $search_string parsed for first name
     * @return list<mixed>
     */
    public function search_by_fName(string $sql, $search_string): array
    {
        $name_array = explode(",", $search_string);
        $fName = add_escape_custom(trim($name_array[1]));
        $sql .= sprintf(" WHERE fname LIKE '%s%%' ORDER BY lname, fname", $fName);
        $recordset = sqlStatement($sql);

        $result_array = [];
        while ($result = sqlFetchArray($recordset)) {
            $result_array[] = $result;
        }

        return $result_array;
    }

    /**
     *   Function that returns an array containing the
     *   Results of a Full Name search
     *   @param string $sql base sql query
     *   @param string $search_string parsed for first, last and middle name
     * @return list<mixed>
     */
    public function search_by_FullName(string $sql, $search_string): array
    {
        $name_array = explode(",", $search_string);
        $lName = add_escape_custom($name_array[0]);
        $fName = add_escape_custom(trim($name_array[1]));
        $sql .= sprintf(" WHERE fname LIKE '%%%s%%' AND lname LIKE '%s%%' ORDER BY lname, fname", $fName, $lName);
        $recordset = sqlStatement($sql);

        $result_array = [];
        while ($result = sqlFetchArray($recordset)) {
            $result_array[] = $result;
        }

        return $result_array;
    }
}
