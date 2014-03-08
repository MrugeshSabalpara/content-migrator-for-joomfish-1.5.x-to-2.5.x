->First to create a database connection with your database  as I am doing these with my local machine my setup criteria is like
host name = localhost
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
/*SCRIPT TO MIGRATE ARTICLE CONTENT DATA FROM JOOMFISH FOR JOOMLA 1.5 TO JOOMLA 2.5.X*/

->First to create a database connection with your database  as I am doing these with my local machine my setup creteria is like
hostname = localhost
dbname = glossary(Note:- glossary is my database nam, so replace it with your database name.)
username = root(Note:- replace it with your name.)
password = ''(Note:- replace it with your password.)
//DB CONNECTION
$db = new PDO("mysql:host=localhost;dbname=glossary;charset=UTF-8", "root", "");


->Here,jos_jf_content is table related to joomfish ,which contains the reference to original contentof jos_content table.
//jos_jf_content table have a reference to original content from jos_content and reference_table = 'content' denotes ARTICLE's table, with distinguish tbl_content TABLE
//MASTER QUERY TO GET NUMBER OF ARTICLES FROM OLD jos_jf_content TABLE RELATED TO PARTICULAR LANGUAGE.--->1
 $stmt = $db->prepare("select distinct(jfc.reference_id),c.catid,jfc.language_id,c.modified,c.modified_by,c.version,c.modified_by ,c.ordering,c.created_by,c.metadesc ,c.created_by_alias  from  jos_jf_content  jfc ,jos_content c where  jfc.reference_id = c.id and jfc.reference_table = 'content' ");
 $stmt->execute();

 //CREATE FETCHING OBJECT FOR THE MASTER QUERY.
 $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

 //LOOP's THE OBJECT TO FETCH VALUE.
    foreach($results as $row) {
  
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
            $fulltext ="";
            $introtext ="";
            $alias ="";
            $published ="";
      
        while($col = $count_row->fetch(PDO :: FETCH_ASSOC))
        {   
            if($col['reference_field'] == "title")
                    {
                        $title =  $col['value'];
                    }
                    if($col['reference_field'] == "fulltext")
                    {
                        $fulltext =  $col['value'];
                    }
                    if($col['reference_field'] == "introtext")
                    {
                        $introtext =  $col['value'];
                    }
                    if($col['reference_field'] == "alias")
                    {
                        $alias =  $col['value'];
                    }
                    $published = $col['published'];
          
        }
      
  //INSERT COLLECTED DATA INTO content TABLE.--->5
  $exe = $db->prepare("insert into tbl_content (`title`,`alias`,`introtext`,`fulltext`,`state`,`catid`,`created`,`created_by`,`created_by_alias`,`modified`,
`modified_by`,`version`,`ordering`,`metadesc`,`language`) values(:title,:alias,:introtext,:fulltext,:published,:categoryid,:created,:created_by,:created_by_alias,:
modified,:modified_by,:version,:ordering,:metadesc,:language_code)");
 
  $exe->execute(array(':title' => $title,':alias' => $alias,':introtext' => addslashes($introtext),':fulltext' => addslashes($fulltext),':published' => ".$published.",':categoryid' => $row['catid'],':created' => date("Y-m-d H:i:s"),':created_by' => $row['created_by'],':created_by_alias' => "".$row['created_by_alias']."",':modified' => date("Y-m-d H:i:s"),':modified_by' =>$row['modified_by'],':version' => $row['version'],':ordering' => $row['ordering'],':metadesc' => $row['metadesc'],':language_code' => $language_code));
 
  //LAST INSERT ID,NEEDS TO BE MAP WITH tbl_assests TABLE and tbl_jf_translationmap TABLE.
  $i = $db->lastInsertId('id');
 
  //GET ASSET ID FOR CATEGORY ID, DERIVED.--->6
  $ass =  $db->prepare("select asset_id from tbl_categories where id = ? ");
  $ass->bindValue(1, $row['catid']);
  $ass->execute();
  $ass_id = $ass->fetch(PDO::FETCH_OBJ);
  $cassetid = $ass_id->asset_id;

  //SELECT MAX lft and rgt from tbl_assests FOR ORDERING.--->7
  $sel =  $db->prepare("select lft,rgt FROM `tbl_assets` where id = (SELECT max(id) FROM `tbl_assets`)");
  $sel->execute();
  $select = $sel->fetch(PDO::FETCH_OBJ);
  $left = $select->lft;
  $right = $select->rgt;
  $left=$left+1;
  $right = $right+1;

     //INSERT DATA FETCHED FROM 5,6,7 IN tbl_assests TABLE.--->8
    $stmt = $db->prepare("insert into  tbl_assets (`parent_id`,`lft`,`rgt`,`level`,`name`,`title`) values(:cassetid,:left,:right,:level,:name,:title)");
    $stmt->execute(array(':cassetid' => $cassetid,':left' => $left,':right' => $right,':level' => 4,':name' => "com_content.article.".$i,':title' => $title));
  
    //LAST INSERT ID,NEEDS TO BE MAP WITH tbl_content TABLE.--->9
    $insertedId = $db->lastInsertId('id');
    //UPDATE asset_id FIELD IN tbl_content TABLE WITH $insertedId.--->10
    $update = $db->prepare("update tbl_content set asset_id = ? where id = ?");
    $update->bindValue(1, $insertedId);
    $update->bindValue(2, $i);
    $update->execute();
  
    //INSERT DATA INTO tbl_jf_translationmap FOR MAPPING BETWEEN tbl_content,tbl_languages,tbl_jf_translationmap TO MAKE JOOMFISH ENTRIES.--->11
    $stmt = $db->prepare("insert into tbl_jf_translationmap (language,reference_id,translation_id,reference_table) values (:language_code,:reference_id,:translation_id,:content)");
    $stmt->execute(array(':language_code' => $language_code,':reference_id' => $row['reference_id'],':translation_id' => $i,':content' => 'content'));
}
?>
