<?php

class Katam extends Controller 
{
   // Home
   function root($f3) 
   {
        $f3->set('title','Kataman');
        
        $db=$this->db;
        $f3->set('groups',$db->exec('SELECT Grp FROM groups ORDER BY Updated DESC'));
        
        echo \Template::instance()->render($f3->UI.'root.htm');     	
   }

    //create group form
	function createGroupForm($f3,$params) 
	{
        if ($f3->get('SESSION.Grp')) 
			$f3->reroute('/'.$f3->get('SESSION.Grp'));
        
        $f3->set('title','Membuat Grup');
        $f3->set('errCode',$params['errCode']);
         
        //generate captcha
        $img = new Image();
		$img->captcha('ui/ttf/URW_Chancery_L.ttf',32,5,'SESSION.captcha_code');
        $f3->write( 'ui/images/captcha.png', $img->dump('png') );
        
        echo \Template::instance()->render($f3->UI.'creategroup.htm');	
    }
    
    //edit group form
    function editGroupForm($f3,$params) 
    {
		if (!$f3->get('SESSION.Grp'))
		{
			$f3->set('SESSION.ErrCode',22);
			$f3->reroute('/in');
		}	
		
		$grp=$f3->get('SESSION.Grp');
		$db=$this->db;
		$f3->set('group',$db->exec("SELECT Info,Mail,Private FROM groups WHERE Grp='$grp'"));
		
		echo \Template::instance()->render($f3->UI.'editgroup.htm');
	}
	
    //login form
	function login($f3,$params) 
	{
        $f3->set('title','Login');
        $f3->set('fail',$params['fail']);
        $grp=strtolower($params['group']);

	    $db=$this->db;
		$result = $db->exec("SELECT Gid,Private FROM groups where Grp='$grp'");
        if ((strlen($grp)>4) && $result) 
        {
           $f3->set('grp',$grp);
           $f3->set('private',$result[0]['Private']);
        }
         
        echo \Template::instance()->render($f3->UI.'login.htm');	
    }

	function auth($f3) 
	{
        $grp=strtolower(trim($f3->get('POST.Grp')));
        $pass=addslashes($f3->get('POST.Pass'));
        
        $reroute=$f3->get('POST.LastUrl')?$f3->get('POST.LastUrl'):"/$grp";
        
        $db=$this->db;
        $group=$db->exec("SELECT HashPass FROM groups where Grp='$grp'");
        
        if (!$group) 
		    $f3->reroute("/in/$grp/1");
		
		$hashPass = $group[0]['HashPass'];
		    
		if(password_verify($pass, $hashPass)) 
		{
			$f3->clear('SESSION');
			$f3->set('SESSION.Grp', $grp);
			$f3->reroute($reroute);
		} 
		else
		    $f3->reroute("/in/$grp/1");
	}
		
	function editGroup($f3) 
	{
		
		$f3->set('SESSION.ErrCode', 0);
		$f3->set('SESSION.OkCode', 0);
		
		if (!$f3->get('POST.Mail'))
		{
			$f3->set('SESSION.ErrCode', 31);
			$f3->reroute('/egr');
		}
		
		$grp=$f3->get('SESSION.Grp');
		$db=$this->db;
		$group=$db->exec('SELECT Mail,HashPass from groups WHERE Grp=?', $grp);
				
		if ($f3->get('POST.Mail')!==$group[0]['Mail'])
		{
			$f3->set('SESSION.ErrCode', 32);
			$f3->reroute('/egr');
		}
		
		/*
		if (!empty($f3->get('POST.Mail0'))) 
		{
		  $audit = \Audit::instance();
		  if( !$audit->email($f3->get('POST.Mail0'), FALSE))
              $f3->reroute('/egr');
	    }
	    */

        $pass=addslashes($f3->get('POST.Pass'));
        $pass0=addslashes($f3->get('POST.Pass0'));
        $hashPass=$group[0]['HashPass'];
        $hashPass0=password_hash($pass0, PASSWORD_DEFAULT); 
        $info=$f3->scrub($f3->get('POST.Info'));
        $private=$f3->get('POST.Private');

        if( $pass!==$pass0 )        
		  if(password_verify($pass, $hashPass)) 
		  {	
			$db->exec('UPDATE groups SET Info=:info,HashPass=:hashPass0,Private=:private WHERE Grp=:grp',
				array(':info'=>$info,':hashPass0'=>$hashPass0,':private'=>$private,':grp'=>$grp)
			);
			$f3->set('SESSION.OkCode', 31);
			$f3->reroute('/egr');
		  } 
		  else 
		  {
			$f3->set('SESSION.ErrCode', 33);  
			$f3->reroute('/egr');
		  }

        if ( (strlen($pass)==0) && (strlen($pass0)==0) ) 
        {
		   $db->exec('UPDATE groups SET Info=:info, Private=:private WHERE Grp=:grp',
				array(':info'=>$info,':private'=>$private,':grp'=>$grp)
		   );
		   $f3->set('SESSION.OkCode', 32);
		   $f3->reroute('/egr');
		}

	}

	function createGroup($f3) 
	{
        $f3->set('SESSION.POST', $_POST);
        
        $grp=strtolower(trim($f3->get('POST.Grp')));
		   if (strlen($grp) < 5) 
		      $f3->reroute('/cg/11');
		   if (strlen($grp) > 15) 
		      $f3->reroute('/cg/12');

        if(!ctype_alnum(str_replace("-","",$grp)))
			$f3->reroute('/cg/15');
			
		$pass=addslashes($f3->get('POST.Pass'));
		$private=$f3->get('POST.Private');
		if ( $private && (strlen($pass)==0) )
			$f3->reroute('/cg/16');
				         
		$db=$this->db;
		if ($db->exec("SELECT Gid FROM groups where Grp='$grp'"))
			$f3->reroute('/cg/14');
		
		if ($f3->get('POST.CaptchaCode')!==$f3->get('SESSION.captcha_code'))
			$f3->reroute('/cg/17');
		
		$audit = \Audit::instance();
		if( !$audit->email($f3->get('POST.Mail'), FALSE))
            $f3->reroute("/cg/13");

		$pass=addslashes($f3->get('POST.Pass'));
		$hashPass=password_hash($pass, PASSWORD_DEFAULT);
		$mail=$f3->get('POST.Mail');
		$info=$f3->scrub($f3->get('POST.Info'));
		$private=$f3->get('POST.Private');
		
		$db->exec('INSERT INTO groups(Grp,Info,HashPass,Mail,Private) 
		VALUES(:grp,:info,:hashPass,:mail,:private)',
			array(':grp'=>$grp,':info'=>$info,':hashPass'=>$hashPass,':mail'=>$mail,':private'=>$private)
		);
		
		$f3->set('SESSION.Grp',$grp);
		$f3->reroute('/'.$grp);
	}

	function newEdition($f3) 
	{
		$postGid=$f3->get('POST.Gid');
		$postGrp=$f3->get('POST.Grp');
		$postEdition=$f3->get('POST.Edition');

		$db=$this->db;
		$ajza=$db->exec("SELECT count(Gid) as JuzKhatam FROM ajza WHERE Khatam=1 AND Gid=$postGid AND Edition=$postEdition");
		
		if ( ($f3->get('SESSION.Grp')===$postGrp) && ($ajza[0]['JuzKhatam']==30) )
			$db->exec('INSERT INTO editions VALUES('.$postGid.','.time().')');
		
		$f3->reroute('/'.$f3->get('POST.Grp'));
	}

    //edit Juz Qari
	function editQariForm($f3,$params) 
	{
        $f3->set('title','Edit Qari');

		$gid=$params['gid'];
		$juz=$params['juz'];
		$errCode=$params['errCode'];
		if ($errCode)
			$f3->set('errCode',$errCode);
        
        $f3->set('gid',$gid);
        $f3->set('juz',$juz); 

		
		$db=$this->db;	
		$group=$db->exec('SELECT Grp,HashPass FROM groups where Gid='.$gid);
        $f3->set('grp',$group);  
		
		//Check login session
		if (!$f3->get('SESSION.Grp'))
		{
			if(password_verify("", $group[0]['HashPass'])) 
				$f3->set('SESSION.Grp',$group[0]['Grp']);
			else
			{
				$f3->set('SESSION.LastUrl',$f3->get('PATH'));
				$f3->set('SESSION.ErrCode',21);
				$f3->reroute('/in/'.$group[0]['Grp']);
			}
		} 
		elseif ($f3->get('SESSION.Grp')!==$group[0]['Grp'])
		{
		      $f3->set('SESSION.Other',$group[0]['Grp']);
		      $f3->set('SESSION.Privat',0 );
		      $f3->reroute('/'.$group[0]['Grp'].'/1');
		}
			
		$edition=$db->exec("SELECT Edition FROM editions WHERE Gid=$gid ORDER BY Edition DESC LIMIT 1");
		$edition=$edition[0]['Edition'];
        $f3->set('edition',$edition);
		
        $f3->set('qari',$db->exec('SELECT Qari,Page,Khatam FROM ajza where Gid='.$gid.' AND Juz='.$juz.' AND Edition='.$edition));

        $endOfJuz=$f3->get('EOJ');
        for($p=$endOfJuz[$juz-1]+1; $p<=$endOfJuz[$juz]; $p++)
			$pages[]=$p;
		
		$f3->set('Pages', $pages);

        echo \Template::instance()->render($f3->UI.'editqari.htm');	
    }
	
	//handle ejq data form
	function editQari($f3 ) 
	{
		$juz=$f3->get('POST.Juz');
		$gid=$f3->get('POST.Gid');
        $qari=str_replace("'","''",trim($f3->get('POST.Qari')));
		   if (strlen($qari) < 3) 
		      $f3->reroute("/eqf/$gid/$juz/1");
		   if (strlen($qari) > 15) 
		      $f3->reroute("/eqf/$gid/$juz/2");		 
        $now=time();
		$page=$f3->get('POST.Page');
		$edition=$f3->get('POST.Edition');

		$db=$this->db;
		$db->exec("UPDATE groups SET Updated=$now WHERE Gid = $gid");
		
        $endOfJuz=$f3->get('EOJ');
		$lastpage=$endOfJuz[$juz];
		$khatam=(intval($page)<$lastpage)?0:1;
				      
	    	$recorded="SELECT Qari from ajza WHERE Gid=$gid AND Juz=$juz AND Edition=$edition";
		$update='UPDATE ajza SET Page=:page, Khatam=:khatam, Updated=:now 
			WHERE Gid=:gid AND Juz=:juz AND Page<:lastpage AND Edition=:edition';
		$insert='INSERT INTO ajza(Gid,Juz,Page,Khatam,Qari,Edition) VALUES(:gid,:juz,:page,:khatam,:qari,:edition)';		
		
		if ($db->exec($recorded))
			$db->exec($update,
				array(':page'=>$page,'khatam'=>$khatam,':now'=>$now, 
			':gid'=>$gid,':juz'=>$juz,':lastpage'=>$lastpage,':edition'=>$edition)
			);
		else
			$db->exec($insert,
			   array(':gid'=>$gid,':juz'=>$juz,':page'=>$page,':khatam'=>$khatam,':qari'=>$qari,':edition'=>$edition)
			);
		
		$f3->reroute('/'.$f3->get('POST.Grp'));
		
	}

	//! Show group info
	function group($f3,$params) 
	{
		$grp=$params['group'];
		if (strlen($grp) < 5) 
		{
		   /*
		   //This will change url
		   $f3->reroute('/nf');
		   */
		   //Using this will not change url
		   echo \Template::instance()->render($f3->UI.'notfound.htm'); 
		   exit();
 		}
    	   		
		$db=$this->db;		
		$group=new DB\SQL\Mapper($db,'groups');	
		$group->load(array('Grp=?',$grp));
		
		if ($group->dry()) 
		{
		   //same reason as before.
		   echo \Template::instance()->render($f3->UI.'notfound.htm'); 
		   exit();
 		}
 		
 		if ($group->Private) 
 		{ 
		   $f3->set('SESSION.Privat',1 );
 		   if (!$f3->get('SESSION.Grp')) 
 		      $f3->reroute("/in/$grp");
		   elseif (($f3->get('SESSION.Grp')!==$grp))
		   {
		      $f3->set('SESSION.Other',$grp);
		      $f3->reroute('/'.$f3->get('SESSION.Grp').'/1');
		   }
		 }
		      
		$f3->set('attr', $params['attr']);      
		$result=$db->exec("SELECT Edition FROM editions WHERE Gid=$group->Gid ORDER BY edition DESC LIMIT 1");
        $edition=$result[0]['Edition'];
        $f3->set('editionDate',date('d-m-Y',$edition));        
        $f3->set('edition',$edition);        
        
		$ajza=$db->exec('SELECT Juz, Qari, Page, Khatam FROM ajza WHERE Gid='.$group->Gid.' AND Edition='.$edition);
        $endOfJuz=$f3->get('EOJ');		
		foreach ($ajza as $val) 
		{
		   $status = $val['Page']-$endOfJuz[$val['Juz']];
		   $qari[$val['Juz']] = array($val['Qari'], $status?$status:'Ok' );
		}
		
		for ($j = 1; $j <= 30; $j++) 
			$juzs[$j] = array('Juz_'.sprintf("%02d",$j), $qari[$j][0]?$qari[$j][0]:'___', $qari[$j][1]?$qari[$j][1]:'___');
		
        $f3->set('grp',$grp);
        $f3->set('title','Group '.$grp);
        $f3->set('gid',$group->Gid);
        $f3->set('updatedDate',date('d-m-Y',$group->Updated));
        $f3->set('juzs',$juzs);
        $f3->set('juzkhatam',$db->exec("SELECT count(Juz) as juzkhatam FROM ajza WHERE Gid=$group->Gid AND Khatam=1 AND Edition=$edition"));
        
        if ($params['attr']=='c')
			echo \Template::instance()->render($f3->UI.'groupplain.htm');
        else
			echo \Template::instance()->render($f3->UI.'group.htm');
	}
	 
	function notFound($f3) 
	{
        $f3->set('title','Tidak ditemukan');
        echo \Template::instance()->render($f3->UI.'notfound.htm');	
	}

	function about($f3) 
	{
        $f3->set('title','About kataman');
        echo \Template::instance()->render($f3->UI.'about.htm');	
	}

	function test($f3) 
	{
        $f3->set('title','Testing');
        
        $f3->set('test','Testing variable to debug');
        
        echo \Template::instance()->render($f3->UI.'test.htm');	
	}

	//! Terminate session
	function logout($f3) 
	{
		$f3->clear('SESSION');
		$f3->reroute('/');
	}
}
