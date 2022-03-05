<!-- 
    Basic PHP page/program to generate large number of random people,
    write the data to csv file, upload the data to SQLite db and
    return a count of the number of records.
    Build with XAMMP to run apache server and connect to SQLlite.
    Create on: 21-02-202
    Author: Wihan.pre
    Version: 1.1.8

    Requires: PHP 5.5 ++
              SQLite 1.0 ++
              Apache 2.4 ++

    Index Page
-->
<?php 
    //echo "Good day user.";  //Start statement.

    //*********Generation and CSV SECTION******************//
    //Funciton to generate random persons.
    function random_peeps($x){
        //declaring array of 20 names.
        $names = array("Wihan", "Andro", "Divan", "Andre", "Sanet", "Bobbert", "Doogle", "Danny", "Lydia", "Frank", 
        "Sam", "Alwyn", "Eldely", "Rion", "Craig", "Genivive", "Simone", "Andries", "Andrew", "Rowan");

        //declaring array of 20 surnames.
        $surnames = array("Pretorius", "de Nobrega", "Wessels", "Cruise", "Kilmer", "Voges", "Roux", "Izzard", 
        "Son", "Lampard", "Cole", "Merwe", "Coombs", "Kenny", "Letterman", "Carry", "Rankine", "Binneman", "Frey", "Klomp");

        //declaring variables.
        $people_list = array();
        $count = 1;
        $ini_tial ="";

        //This array asist in chaecking for duplicates - Comented out, passed 1 out of 5 tests.
        //$dup_array = array();

        //while loop to generate peeps up to the number entered by user.
        while($count<=$x){
            $name = $names[array_rand($names)];
            $ini_tial = $name[0];
            $surname = $surnames[array_rand($surnames)];
            $date = mt_rand(1, time());
            $display_DOB = date("d/m/Y",$date);
            $random_DOB = date("Y/m/d",$date);
            //$now = date("d/m/Y");  // This line was part of "manual date generator to include dates before 1970
            $age = intval(date('Y', time() - strtotime($random_DOB))) - 1970;
            $rand_person = ($count.", ".$name.", ".$surname.", ".$ini_tial.", ".$age.", ".$display_DOB); //Original line
            
            //this commented out section omits duplicate entries without taking the IDs into account, passed 1 out of 5 tests..
            //$dup_peep = ($name.", ".$surname.", ".$ini_tial.", ".$age.", ".$display_DOB);
            //array_push($dup_array,$dup_peep);
            //if (!in_array($dup_peep, $dup_array)){
            //    array_push($dup_array,$dup_peep);
            //    array_push($people_list, $rand_person);
            //    $count++;
            //}

            array_push($people_list, $rand_person);
            $count++;
        }
        return  $people_list;
    }

    //Running isset to take user input and call function to genrate random persons.
    if(isset($_POST['Generate'])){

        //Storing user input to variable to call function.
        $num_peeps = htmlentities($_POST['num_peeps']);
        
        //calling function. 
        $people_list = random_peeps($num_peeps);

        //Writing generated persons to csv file and outputting to folder.
        $out_file = fopen("csvOutput/output.csv", "w");
        $comn_name = "ID, NAME, SURNAME, INIT, AGE, DATE OF BIRTH"."\n";
        fwrite($out_file, $comn_name);
        foreach ($people_list as $persons) {
            $son = explode(", ",$persons);
            fputcsv($out_file, $son);
        }
        fclose($out_file);
        $done = "People generated!";
    }

    //*********Database SECTION******************//
    //Running isset to create databse, import data and return count of records.
    if (isset($_POST['count'])){
        //SQL statement to create database
        $peeps_base = new SQLite3("peeps_db");

        //SQL statement to create table in database and add relevant columns and data types.
        $peeps_base -> exec("CREATE TABLE IF NOT EXISTS peeps_table (id INT, name TEXT, surname TEXT, init CHAR, age TEXT, dob DATE)");
        $peeps_base -> exec("DELETE FROM peeps_table"); //control SQL statement to clear data base before next test.

        //Row variable to identify first row to be appended to batch insert.
        $row = 1;
        if (($handle = fopen("csvOutput/output.csv", "r")) !== FALSE) {
            //Removing headers from CSV file and moving pointer.
            fgetcsv($handle, 1000, ","); 
            //Declaring base SQL statement for batch insert
            $sql = "INSERT INTO peeps_table(id, name, surname, init, age, dob) VALUES";
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                //First row indentified and appending values to batch insert statement. No need to have the comma at before concatenation.
                if ($row == 1)
                    $sql .= "($data[0],'$data[1]','$data[2]','$data[3]','$data[4]','$data[5]')";
                //Appending the rest of the rows. Comma added before concatenation for SQL syntax.
                else
                    $sql .= ", ($data[0],'$data[1]','$data[2]','$data[3]','$data[4]','$data[5]')";
                $row++;
            }
            //Excecuting single batch insert query
            $peeps_base -> exec($sql);
            //Closing file.
            fclose($handle);
        }

        //Control print statement to retrive and display data from data base.
        //$resulst = $peeps_base->query('SELECT * FROM peeps_table');
        //while ($row = $resulst->fetchArray()) {
        //    echo "{$row['id']}, {$row['name']}, {$row['surname']}, {$row['init']}, {$row['age']}, {$row['dob']}"."<br>";
        //}

        //returning count of records in db
        $record_count = $peeps_base -> querySingle("SELECT COUNT(*) as count FROM peeps_table");
    }

?>
<!DOCTYPE html>
<html>
  
<body>
    <center>
         <!--Form to take in number of persons to generate.-->
        <h3>Generate People:</h3>
        <form action="index.php", method="POST">
            <div><label>How many poeple would you like to generate?</label>
                <input type="number", name="num_peeps", id="num_peeps" value="">
                <button type="submit", name="Generate", value="Generate">Submit</button>
                <br>
                <div><p style="color: red"><?php echo $done; ?></p></div>
            </div>
        </form>
           
        <!--*********CSV Upload and HTML Table Section******************-->
        <!--Form to load csv file and display data in table.-->
        <h3>People in CSV file:</h3>
        <form id="frm-upload" action="" method="POST" enctype="multipart/form-data">
            <div class="form-row">
            <div>Browse for file:</div>
                <div>
                    <input type="file" class="file-input" name="file-input">
                </div>
            </div>

            <div class="button-row">
                <input type="submit" id="btn-submit" name="upload" value="Upload">
            </div>
        </form>
        <!--Running php isset to choose file.-->
        <?php if(!empty($response)) { ?>
            <div class="response <?php echo $response["type"]; ?>">
            <?php echo "message"; ?></div>
            <?php 
        }?>
        <!--Running php isset to open read and display csv data in table.-->
        <?php
            if(!empty(isset($_POST["upload"]))) {
                if (($csv_file = fopen($_FILES["file-input"]["tmp_name"], "r")) !== FALSE) { ?>
            <table class="tutorial-table" width="50%" border="10" cellspacing="1">
            <?php
            //While loop to populate table.
                $i = 0;
                while (($row = fgetcsv($csv_file)) !== false) {
                    $class ="";
                if($i==0) {
                    $class = "header";}
                    ?>
                <tr>
                    <td class="<?php echo $class; ?>"><?php echo $row[0]; ?></td>
                    <td class="<?php echo $class; ?>"><?php echo $row[1]; ?></td>
                    <td class="<?php echo $class; ?>"><?php echo $row[2]; ?></td>
                    <td class="<?php echo $class; ?>"><?php echo $row[3]; ?></td>
                    <td class="<?php echo $class; ?>"><?php echo $row[4]; ?></td>
                    <td class="<?php echo $class; ?>"><?php echo $row[5]; ?></td>
                </tr>
        <?php
            $i ++;}
            fclose($csv_file);
        ?>
    </table>
    <?php
        $response = array("type" => "success", "message" => "CSV displayed in HTML.");
        } else {
            $response = array("type" => "error", "message" => "Error - CSV not loaded");
            }
        }
    ?>
    </div>
    <?php if(!empty($response)) { ?>
        <div class="response <?php echo $response["type"]; ?>">
        <?php echo $response["message"]; ?></div>
    <?php }?>
    
    <!--Form to display record count-->
    <h3>Return count of records in data base:</h3>
    <form action="index.php", method="POST">
        <div>
            <button type="submit", name="count", value="count">Request</button>
            <br>
            <div><p><?php echo "The total number of records are: ".$record_count; ?></p></div>
        </div>
        </form>


    </center>
</body>
  
</html>