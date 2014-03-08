->First to create a database connection with your database  as I am doing these with my local machine my setup criteria is likehost name = localhost
db name = glossary(Note:- glossary is my database nam, so replace it with your database name.)
username = root(Note:- replace it with your name.)
password = ''(Note:- replace it with your password.)

I have old table prefix like 'jos_' and for new table prefix as 'tbl_'.
//DB CONNECTION
$db = new PDO("mysql:host=localhost;dbname=glossary;charset=UTF-8", "root", "");



<?php
/*NOTE*/
/*----*/
/*SCRIPT WILL WORK AFTER MIGRATING DATA FROM JOOMLA 1.5 TO JOOMLA 2.5.X. MAKE SURE DATABASE REQUIRED TABLES ARE AVAIL*/
/*SCRIPT TO MIGRATE MENU DATA FROM JOOMFISH FOR JOOMLA 1.5 TO JOOMLA 2.5.X*/

//DB CONNECTION
$db = new PDO("mysql:host=localhost;dbname=glossary;charset=UTF-8", "root", "");

//jos_jf_menu table have a reference to original content from tbl_menu and reference_table = 'menu' denotes menu's table, with distinguish tbl_menu TABLE
//MASTER QUERY TO GET NUMBER OF MENU FROM OLD jos_jf_content TABLE RELATED TO PARTICULAR LANGUAGE.--->1
 $stmt = $db->prepare("select distinct(jfc.reference_id),m.id,jfc.language_id,m.level,m.parent_id,m.menutype,m.checked_out
,m.ordering,m.access,m.home,m.component_id from  jos_jf_content  jfc ,tbl_menu m where  jfc.reference_id = m.id and jfc.reference_table = 'menu' ");
 $stmt->execute();

 //CREATE FETCHING OBJECT FOR THE MASTER QUERY.
 $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //LOOP's THE OBJECT TO FETCH VALUE.
    foreach($results as $row) {
        $lang_id = $row['language_id'];
        $parent_id = $row['parent_id'];
        $level = $row['level'];
        $menutype = $row['menutype'];
        $checked_out = $row['checked_out'];
        $ordering = $row['ordering'];
        $access = $row['access'];
        $home = $row['home'];
        $component_id = $row['component_id'];
       
       
        
          //SELECT MAX lft and rgt from tbl_assests FOR ORDERING.--->4
        $sel =  $db->prepare("select lft,rgt FROM `tbl_menu` where id = (SELECT max(id) FROM `tbl_menu`)");
        $sel->execute();
        $select = $sel->fetch(PDO::FETCH_OBJ);
        $left = $select->lft;
        $right = $select->rgt;
        $left=$left+1;
        $right = $right+1;
       
       
        //IDENTIFY EACH ARTICLE FROM REFERENCE ID AND LANGUAGE ID DERIVED FROM MASTER QUERY.--->2
        $count_row = $db->prepare("select * from jos_jf_content where reference_id = ? and language_id = ?");
        $count_row->bindValue(1, $row['reference_id']);
        $count_row->bindValue(2, $row['language_id']);
       
       
        //DERIVE LANGUAGE CODE FROM LANGUAGE ID.--->3
        $lang_code = $db->prepare("select lang_code from tbl_languages where lang_id= ?");
        $lang_code->bindValue(1, $row['language_id']);
        $lang_code->execute();
        $l_code = $lang_code->fetch(PDO::FETCH_OBJ);
           $language_code = $l_code->lang_code;
       
        //EXECUTE --->2
        $count_row->execute();
       
            //LOOPING DATA AND STORES IN VARIABLE.
            $title ="";
            $link ="";
            $alias ="";
            $path ="";
            $published ="";
            $type="component";
           
       
        while($col = $count_row->fetch(PDO :: FETCH_ASSOC))
        {    
            if($col['reference_field'] == "name")
                    {
                        $title =  $col['value'];
                    }
                    if($col['reference_field'] == "link")
                    {
                        $link =  $col['value'];
                    }
                    if($col['reference_field'] == "alias")
                    {
                        $alias = $col['value'];
                        $path = $col['value'];
                    }
                    $published = $col['published'];
        }
       
       
       
        //INSERT COLLECTED DATA INTO menu TABLE.--->5
  $exe = $db->prepare("insert into tbl_menu (`menutype`,`title`,`alias`,`path`,`link`,`type`,`published`,`parent_id`,`level`,`component_id`
,`ordering`,`checked_out`,`checked_out_time`,`access`,`lft`,`rgt`,`home`,`language`) values(:menutype,:title,:alias,:path,:link,:type,:published,:parent_id,:level,:component_id
,:ordering,:checked_out,:checked_out_time,:access,:left,:right,:home,:language_code)");
 
  $exe->execute(array(':menutype' => $menutype,':title' => $title,':alias' => $alias,':path' => $path,':link' => $link,':type' => $type,':published' => ".$published.",':parent_id' => $parent_id,':level' => $level,':component_id' => $component_id,':ordering' => $row['ordering'],':checked_out' => "".$checked_out."",':checked_out_time' => date("Y-m-d H:i:s"),':access' =>$access,':left' => $left,':right' => $right,':home' => $home,':language_code' => $language_code));
 
  //LAST INSERT ID,NEEDS TO BE MAP WITH tbl_jf_translationmap TABLE.
  $i = $db->lastInsertId('id');
 
  //INSERT DATA INTO tbl_jf_translationmap FOR MAPPING BETWEEN tbl_menu,tbl_languages,tbl_jf_translationmap TO MAKE JOOMFISH ENTRIES.--->6
    $stmt = $db->prepare("insert into tbl_jf_translationmap (language,reference_id,translation_id,reference_table) values (:language_code,:reference_id,:translation_id,:content)");
    $stmt->execute(array(':language_code' => $language_code,':reference_id' => $row['reference_id'],':translation_id' => $i,':content' => 'menu'));

}
?>
