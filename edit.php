<?php
/**
 * Serposcope - An open source rank checker for SEO
 * http://serphacker.com/serposcope/
 * 
 * @link http://serphacker.com/serposcope Serposcope
 * @author SERP Hacker <pierre@serphacker.com>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode CC-BY-NC-SA
 * 
 * Redistributions of files must retain the above notice.
 */
include('inc/config.php');
include('inc/define.php');
include('inc/common.php');


$group = null;
$keywords = array();
$sites = array();
if(isset($_GET['idGroup'])){
    
    $qGroup = "SELECT * FROM `".SQL_PREFIX."group` WHERE idGroup = ".intval($_GET['idGroup']);
    $resGroup = mysql_query($qGroup);
    if(($group = mysql_fetch_assoc($resGroup))){
        
        $group['options'] = json_decode($group['options']);
        
        $qKeyword = "SELECT idKeyword,name FROM `".SQL_PREFIX."keyword` WHERE idGroup = ".intval($_GET['idGroup']);
        $resKeyword=mysql_query($qKeyword);
        while($keyword=mysql_fetch_assoc($resKeyword)){
            $keywords[$keyword['idKeyword']]=$keyword['name'];
        }
        
        
        $qSite = "SELECT idTarget,name FROM `".SQL_PREFIX."target` WHERE idGroup = ".intval($_GET['idGroup']);
        $resSite=mysql_query($qSite);
        while($site=mysql_fetch_assoc($resSite)){
            $sites[$site['idTarget']]=$site['name'];
        }        
    }
}

if($group == null){
    die();
}

if(isset($_POST['edit']) && $_POST['edit']=="edit"){
    $keywordsEDIT = array();
    $keywordsADD = array();
    $sitesEDIT= array();
    $sitesADD= array();
    $groupOptions=array();
    
    // KEYWORD
    if(isset($_POST['keywords']) && is_array($_POST['keywords'])){
        foreach ($_POST['keywords'] as $keyword) {
            if(!empty($keyword)){
                $keywordsEDIT[] = $keyword;
                if(!in_array($keyword,$keywords)){
                    $keywordsADD[]=$keyword;
                }
            }
        }
    }

    // delete old keyword
    $qDeleteKW = "DELETE FROM `".SQL_PREFIX."keyword` WHERE idGroup = ".intval($_GET['idGroup']).
            " AND name NOT IN ('".implode("','",array_map('addslashes',$keywordsEDIT))."')";
    mysql_query($qDeleteKW);
    
    // add keyword
    
    if(!empty($keywordsADD)){
        $qAddKW = "INSERT INTO `".SQL_PREFIX."keyword` (idGroup,name) VALUES";
        for($i=0;$i<count($keywordsADD);$i++){
            $qAddKW .= " (".intval($_GET['idGroup']).",'".addslashes($keywordsADD[$i])."') ";
            if($i != count($keywordsADD)-1){
                $qAddKW .= ",";
            }
        }
        mysql_query($qAddKW);
    }
    
    
    // TARGET
    if(isset($_POST['sites']) && is_array($_POST['sites'])){
        foreach ($_POST['sites'] as $site) {
            if(!empty($site)){
                $sitesEDIT[] = $site;
                if(!in_array($site,$sites)){
                    $sitesADD[]=$site;
                }
            }
        }
    }
    
    // delete old site
    $qDeleteSite = "DELETE FROM `".SQL_PREFIX."target` WHERE idGroup=".intval($_GET['idGroup']).
            " AND name NOT IN ('".implode("','",array_map('addslashes',$sitesEDIT))."')";
    mysql_query($qDeleteSite);
    
    // add new site
    if(!empty($sitesADD)){
        $qAddSite = "INSERT INTO `".SQL_PREFIX."target` (idGroup,name) VALUES";
        for($i=0;$i<count($sitesADD);$i++){
            $qAddSite .= " (".intval($_GET['idGroup']).",'".addslashes($sitesADD[$i])."') ";
            if($i != count($sitesADD)-1){
                $qAddSite .= ",";
            }
        }
        mysql_query($qAddSite);
    }
    
    // group option
    $moduleOptions=$modules[$group['module']]->getGroupOptions();
    foreach ($moduleOptions as $option) {
        if(!isset($_POST[$option[0]]))
            die(header("Location: edit.php?idGroup=".intval($_GET['idGroup'])."&error=".urlencode("Missing group option ".$option[0])));
        else if(!preg_match($option[3], $_POST[$option[0]]))
            die(header("Location: edit.php?idGroup=".intval($_GET['idGroup'])."&error=".
                urlencode("Invalid group option ".$option[0]." ".htmlentities ($_POST[$option[0]])." doesn't match ".$option[3])
            ));
        else{
            $groupOptions[$option[0]] = $_POST[$option[0]];
        }
    }
    
    print_r($groupOptions);
    
    $qUpdateGroup = "UPDATE `".SQL_PREFIX."group` SET ".
            "name = '".addslashes($_POST['name'])."', ".
            "options = '".addslashes(json_encode($groupOptions))."' ".
            "WHERE idGroup = ".intval($_GET['idGroup']);
    
    mysql_query($qUpdateGroup);

    header("Location: view.php?idGroup=".intval($_GET['idGroup']));
}

include("inc/header.php");
?>
<h2>Edit group</h2>
<?php
if(isset($_GET['error'])){
    $error = h8($_GET['error']);
}
if(!empty($error)){
    echo "<div class='alert alert-error'>$error</div>\n";
}
?>
<div>
<form method="POST" class="well form-horizontal" action="edit.php?idGroup=<?= intval($_GET['idGroup']); ?>" >
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="name">Group name</label>
            <div class="controls">
                <input type="text" class="input-xlarge" id="name" name="name" value="<?= h8($group['name']);  ?>" >
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="keywords[]">Keywords</label>
            <ul class="controls keywords" style="list-style-type:none">
<?php
foreach ($keywords as $keyword) {
    echo '<li><input type="text" class="input-xlarge" name="keywords[]" value="'.h8($keyword).'" >'."</li>\n";
}
?>
                <li><input type="text" class="input-xlarge" name="keywords[]" ><img src="img/add.png" class="kwimage" /></li>
            </ul>
        </div>

        <div class="control-group">
            <label class="control-label" for="sites[]">Domain</label>
            <ul class="controls sites" style="list-style-type:none">
<?php
foreach ($sites as $site) {
    echo '<li><input type="text" class="input-xlarge" name="sites[]" value="'.h8($site).'" >'."</li>\n";
}
?>                
                <li><input type="text" class="input-xlarge" name="sites[]" placeholder="www.site.com or *.site.com" ><img src="img/add.png" class="siteimage" /></li>
            </ul>

            <div id="groupopt" >
<?php
foreach ($modules[$group['module']]->getGroupOptions() as $option) {
    echo '<div class="control-group">';
    echo '<label class="control-label" for="'. $option[0] . '">' . $option[0] . '</label>';
    echo '<div class="controls">';
    echo '<input type="text" class="input-xlarge" id="' . $option[0] . '" name="' . $option[0] . '" value="'  .h8(isset($group['options']->$option[0]) ? $group['options']->$option[0] : $option[1]).'" >';
    if($option[2] != "")
        echo '<p class="help-block">' . $option[2] . '</p>';
    echo '</div>';
    echo '</div>';
    echo "\n";
}
?>                
            </div>

            <button class="btn btn-primary" type="submit" name="edit" value="edit" >Edit</button>            
        </div>   
    </fieldset>
</form>
</div>
<script>
    $('.kwimage').click(function(){
        $('.keywords').append(
            '<li><input type="text" class="input-xlarge" name="keywords[]" /></li>'
        );
    });

    $('.siteimage').click(function(){
        $('.sites').append(
            '<li><input type="text" class="input-xlarge" name="sites[]" /></li>'
        );
    });
</script>
<?php
include('inc/footer.php');
?>