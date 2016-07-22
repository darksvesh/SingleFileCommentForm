<?php
//библеотека с классом Website 
//в которой формируется основной код сайта
//TODO:не забыть удалить старое из CSS
//TODO:не забыть удалить TODO

class Website{
private $AllowWithOutSSL = 1;//можно ли использовать сайт без SSL
private $SSLRedirect = "http://127.0.0.1:8001"; //редирект на SSL, сейчас тут у меня порты 8000 и 8001
private $SiteURL = "http://127.0.0.1:8000";	//место,где сайт
private $SiteHead = "Тестовый сайт"; //заголовок веб страницы
private $SiteHat = "Пробная форма комментирования"; //шапка сайта
private $SiteBoots = "Свешников Владимир <br>DarkSvesh "; //башмаки
private $SiteMainPageHat = "Страница для комментирования";
private $SiteMainPageText = "Место под фотографию котиков";
private $Action = ""; //действие
private $ActionString = ""; //дополнительный параметр действия
private $Page = ""; //навигация
private $RootUsername = "root";
private $RootPswd = "udavotis";
private $Database = "userdb";
private $TableName = "messages";
private $Host = "127.0.0.1:3306";

private $Error = "";
private $Admin = 1; 
private $CommentId = "";
private $Username = "";
private $Email = "";
private $Message = "";
private $ParentId = 0;
private $Date = "";

//Html заголовок и прочая лабуда
private function FormHeader($Title){
//стандартный хидер Html5 + чуток локального яваскрипта
//Я потратил слишком много времени 
//печально, но я сказал, что 48 часов с момента четверга примерно 17:00, я мужик или нет*?
//я обещал сделать к этому времени и это все что я сделал за 48 часов минус то, что пришлось делать по дому, увы
	Return "<!DOCTYPE html>
<html lang=ru>
	<head>
		<meta charset=utf-8>
		<title>$Title</title>
		<!--[if IE]>
        <script>
            document.createElement('header');
            document.createElement('nav');
            document.createElement('section');
            document.createElement('article');
            document.createElement('aside');
            document.createElement('footer');
        </script>
    <![endif]-->
	<link rel=\"stylesheet\" type=\"text/css\" href=\"Style.css\">

	<script type=\"text/javascript\">
		function ReplyComment(id, user) {
			document.getElementById('ReplyParentId').value = id;
			document.getElementById('ReplyTarget').style.display = 'inline-block';
			document.getElementById('ReplyTarget').innerHTML = 'Ответить '+user;
			document.getElementById('ReplyTarget').scrollIntoView(true);
			document.getElementById('UnreplyTarget').style.display = 'inline-block';
		}
		function Unreply(){
			document.getElementById('ReplyParentId').value = 0;
			document.getElementById('ReplyTarget').style.display = 'none';
			document.getElementById('UnreplyTarget').style.display = 'none';
		}
		function RemoveComment(CommentId){
			
			var form = document.createElement(\"form\");
			form.setAttribute(\"method\", \"post\");
			form.setAttribute(\"action\", \"index.php\");
			var hiddenField = document.createElement(\"input\");
			hiddenField.setAttribute(\"type\", \"hidden\");
			hiddenField.setAttribute(\"name\", \"Action\");
			hiddenField.setAttribute(\"value\", \"DeleteComment\");
			form.appendChild(hiddenField);
			hiddenField = document.createElement(\"input\");
			hiddenField.setAttribute(\"type\", \"hidden\");
			hiddenField.setAttribute(\"name\", \"CommentId\");
			hiddenField.setAttribute(\"value\", CommentId);
			form.appendChild(hiddenField);
			document.body.appendChild(form);
			form.submit();
		}


    </script>

</head>
<body style=\"background-image:url(background.jpg);background-size: cover;\">";
}

//Низ для Html 
private function FormBottom(){
	Return "
	</body>
</html>";
return;
}

//Тело сайта
private function FormBody(){
	//основной код сайта
	$Hat = $this->FormHat($this->SiteHat);
	$Left = "";//левая колонка
	$Right = "";//правая колонка обе сейчас не нужны
	$Center = $this->FormBlock("MainContentBlock",$this->FormMainContentSection($this->FormPage()));
	//Еще немного ультимативного кода
	$Jacket = (($this->AllowWithOutSSL == 0) && ($this->CheckSSL() == 0))? $this->FormNoSSLError():$this->FormJacket($Left,$Center,$Right);
	$Boots = $this->FormBoots($this->SiteBoots,date('Y'));
	$Costume = $this->FormCostume($Hat,$Jacket,$Boots);
	return $Costume;
}
//текст страницы
private function FormContentText(){
	return 
		"<div class=\"ArticleHeader\">".$this->SiteMainPageText."</div>"
		."<img class=\"PageImage\" src=\"".$this->SiteURL."/kitten.jpeg\">
	";
}
private function PostComment(){
	if(strlen($this->Message)>0){
		if(strlen($this->Username)>0){
			if(filter_var($this->Email, FILTER_VALIDATE_EMAIL)){//валидатор мыла
				if($this->CaptchaCheck()){//валидатор капчи
					$Connection = mysqli_connect($this->Host, $this->RootUsername, $this->RootPswd);
					mysqli_select_db($Connection,$this->Database);	
					$SQLQuery = "SELECT Floor FROM Messages WHERE (idMessage = ".intval($this->ParentId).")";
					$result = mysqli_query($Connection,$SQLQuery);
					$row = mysqli_fetch_array($result, MYSQLI_NUM);
					//mysqli_free_result($result);
					if(intval($row[0])<4){
						$SQLQuery = "INSERT INTO Messages (Username, Email, Message, ParentId, Date, Floor) values (\"";
						$SQLQuery.=$this->Encode($this->Username)."\",\"";
						$SQLQuery.=$this->Encode($this->Email)."\",\"";
						$SQLQuery.=$this->Encode($this->Message)."\",";
						$SQLQuery.=intval($this->ParentId).",\"";
						$SQLQuery.=($this->Date)."\",";
						$SQLQuery.=(intval($row[0])+1).")";
						$result = mysqli_query($Connection,$SQLQuery);
					} else $this->Error = "Достигнут предел вложенности.";
					//mysqli_free_result($result);
					mysqli_close($Connection);
				} else $this->Error = "Неверно введен код с картинки. ";
			} else $this->Error = "Неправильно введен эмейл.";
		} else $this->Error = "Пустое поле Имени";
	} else $this->Error = "Пустое поле Сообщения";
}
private function FindAndRemove($Connection,$Id){
	//незнаю, есть ли смысл удалять полностью, когда можно неотображать D:
	$SQLQuery = "DELETE FROM Messages WHERE(idMessage = ".intval($Id).")";
	$result = mysqli_query($Connection,$SQLQuery);
	$SQLQuery = "SELECT idMessage FROM Messages WHERE (ParentId = ".intval($Id).")";
	$result = mysqli_query($Connection,$SQLQuery);
	while($row = mysqli_fetch_array($result, MYSQLI_NUM)){
		$this->FindAndRemove($Connection,$row[0]);
	}
	return;
}
private function DeleteComment(){
	if($this->Admin){
		$Connection = mysqli_connect($this->Host, $this->RootUsername, $this->RootPswd);
		mysqli_select_db($Connection,$this->Database);	
		$this->FindAndRemove($Connection,$this->CommentId);
		//MySQL вроде не имеет возможности реализации
		//рекурсии в запросах, поэтому нужна или внешняя рекурсия или цикл
		//мускуль сервер не дал мне удалять и делать выборку внутри одного запроса из одной таблицы
		//создание второй таблицы ради удаления из этой б у д е т как-то глупо
		//будем делать костыл..есипед
		//картинку костылесипеда тому кто читает мой код
		//https://pp.vk.me/c7005/v7005770/21152/_xqaTSC7_Ls.jpg
		mysqli_close($Connection);
	}
}
//блок комментов
//рекурсия для обхода древа
//одна ошибка и мы зависли
private function FetchCommentRows($SqlConnection, $id){
	$Comments = "";
	$SQLQuery = "SELECT * FROM Messages WHERE (ParentId = $id)";//корневые - 0
	$result = mysqli_query($SqlConnection,$SQLQuery);
	//лучше так не делать, но я хочу быстрее закончить это, времени из того, что я выбрал осталось мало
	$RemoveStatus = "";
	if(!$this->Admin) $RemoveStatus = "style=\"display:none;\"";
	while($row = mysqli_fetch_array($result, MYSQLI_NUM)){
		$RowCommentId = $row[0];
		$RowUsername = $this->Decode($row[1]);
		$RowMessage = $this->Decode($row[3]);
		$RowParentId = $row[4];
		$RowDate = $row[5];
		$RowStep = $row[6];
		$RowButtons = ($row[6]>3)?"1":"";
		$CommentForm = "	
		<div class=\"CommentLine\">
			<div class=\"UserComment$RowStep\" >
				<div class=\"CommentInfo\">
					<div class=\"СommentUsername\">$RowUsername</div>
					<div class=\"CommentDate\">$RowDate</div>
				</div>
				<textarea class=\"CommentArea\" rows=\"2\" cols=\"64\" readonly>$RowMessage</textarea>

				<div class=\"CommentButtons\">
					<button onclick=\"ReplyComment($RowCommentId,'$RowUsername')\" class=\"CommentReply$RowButtons\">ответить</button>
					<button onclick=\"RemoveComment($RowCommentId)\" $RemoveStatus class=\"CommentRemove\">удалить</button>
				</div>
			</div>
		</div>	
		";
		$Comments .= $CommentForm;
		$Comments .= $this->FetchCommentRows($SqlConnection,$RowCommentId);
	}
	return $Comments;
}
private function FormCommentBlock(){
	
	$Comments = "";
	$Connection = mysqli_connect($this->Host, $this->RootUsername, $this->RootPswd);
	mysqli_select_db($Connection,$this->Database);
	$Comments = $this->FetchCommentRows($Connection,0);
	
	mysqli_close($Connection);
	$Tip = $this->Error;
	return 
	"
	<div class=\"CommentsCaption\">
		Комментарии
	</div>
	".$Comments."
	<div class=\"AddComment\" >
		<div class=\"AddCommentBorder\" >
			<form action=\"index.php\" method=\"post\" class=\"CommentForm\">
				<div class=\"ReplyCaption\">
					<div id=\"ReplyTarget\" class=\"ReplyTarget\">Ответить ...</div>
					<div id=\"UnreplyTarget\" class=\"UnreplyCross\" onclick=\"Unreply()\">
						<img src=\"cross.png\" width=\"10\" height=\"10\">
					</div>
				</div>
				<input id=\"ReplyParentId\" type=\"hidden\" value=\"0\" name=\"ParentId\">
				<input type=\"hidden\" value=\"AddComment\" name=\"Action\">
				
				<input type=\"hidden\" value=\"unused for now\" name=\"PostId\">
				
				
				<div class=\"CommentNameBlock\">
					Представтесь<br>
					<input type=\"text\" class=\"NameInput\" value=\"\" maxlength=\"16\" name=\"Username\">
				</div>
				<div class=\"CommentEmailBlock\">			
					Укажите Email<br>
					<input type=\"email\" class=\"EmailInput\" maxlength=\"64\" value=\"\" name=\"Email\">
				</div>
				<div class=\"CaptchaFrame\">	
					Введите текст с картинки:<br>
					<label class=\"CaptchaFrameLabel\"><img id=\"CaptchaImg\" onclick=\"document.getElementById('CaptchaImg').src = 'index.php?Page=Captcha&Code=' + Math.random()\" style=\"vertical-align: middle;\" width=\"45\" height=\"20\"  src=\"index.php?Page=Captcha&Code=0\"></img></label>
					<input id=\"CaptchaInput\" class=\"CaptchaFrameInput\" maxlength=\"3\" type=\"text\" size=\"15\"  name=\"Captcha\">
				</div>
				<div class=\"CommentTextBlock\">
					<input name=\"Message\" maxlength=\"128\" class=\"CommentInput\" >
					<input type=\"submit\" name=\"commit\" value=\"Ответить\" class=\"CommentButton\">
				</div>
				<div class=\"CommentTipBlock\">
					$Tip
				</div>
			</form>
		</div>    
	</div>
	";
}
//Формируем код страниц
private function FormMainPage(){
	return $this->FormMainContentBlock("MainContent",$this->SiteMainPageHat,$this->FormContentText().$this->FormBlock("CommentsBlock",$this->FormCommentBlock()));
}

//формируем страницу и переход между страницами
private function FormPage(){
//формирователь страниц
	if($this->Page=="")
		$this->Page = "Main";
	switch ($this->Page){
		case "Main": return $this->FormMainPage();
			break;
		default:
			return $this->Form404();
	}
}


//обработка действий
private function CheckActions(){
	//
	$this->Page = ((isset($_POST["Page"]) ? $_POST["Page"]:"")=="")? 
		(isset($_GET["Page"]) ? $_GET["Page"]:""):$_POST["Page"];
		
	$this->Username = ((isset($_POST["Username"]) ? $_POST["Username"]:"")=="")? 
		(isset($_GET["Username"]) ? $_GET["Username"]:""):$_POST["Username"];
	
	$this->Email = ((isset($_POST["Email"]) ? $_POST["Email"]:"")=="")? 
		(isset($_GET["Email"]) ? $_GET["Email"]:""):$_POST["Email"];
	
	$this->Message = ((isset($_POST["Message"]) ? $_POST["Message"]:"")=="")? 
		(isset($_GET["Message"]) ? $_GET["Message"]:""):$_POST["Message"];
	
	$this->ParentId = ((isset($_POST["ParentId"]) ? $_POST["ParentId"]:"")=="")? 
		(isset($_GET["ParentId"]) ? $_GET["ParentId"]:""):$_POST["ParentId"];
	if($this->Admin != 1){
		$this->Admin = ((isset($_POST["Admin"]) ? $_POST["Admin"]:"")=="")? 
			(isset($_GET["Admin"]) ? $_GET["Admin"]:""):$_POST["Admin"];
	}
	//SQL daytime format
	$this->Date = date ("Y-m-d H:i:s");	
		
	$this->CommentId = ((isset($_POST["CommentId"]) ? $_POST["CommentId"]:"")=="")? 
		(isset($_GET["CommentId"]) ? $_GET["CommentId"]:""):$_POST["CommentId"];
	
	$this->Action = ((isset($_POST["Action"]) ? $_POST["Action"]:"")=="")? 
					   (isset($_GET["Action"]) ? $_GET["Action"]:""):$_POST["Action"];
	$Action = $this->Action;
	if($Action !== ""){
		switch ($Action){
			case "DeleteComment": $this->DeleteComment();
				break;
			case "AddComment": $this-> PostComment();			
				break;
			default:
				break;		
		}
	}
	return;
}

//формирователь самой капчи
private function FormCaptcha(){
//знаю, без наворотов, мог сделать и круче, на столько таксебе капча, что ее распознанию ненужна нейронная сеть
	
	$string = "";
	for ($i = 0; $i < 3; $i++) 
		$string .= chr(rand(97, 122));
	$_SESSION["rand_code"] = $string; 
	$image = imagecreatetruecolor(170, 60); 
	$black = imagecolorallocate($image, 10, 110, 0); 
	$color = imagecolorallocate($image, 250, 20, 0); 
	$bg = imagecolorallocate($image, 235, 235, 235); 
	imagefilledrectangle($image,0,0,299,79,$bg); 
	imagettftext ($image, 30, 0, 10, 40, $color, "verdana.ttf", $_SESSION['rand_code']);
	header("Content-type: image/png");
	imagepng($image);
	return;
}
//простая проверка капчи
private function CaptchaCheck(){
	$Result = 0;
	if((isset($_POST["Captcha"]))&&(isset($_SESSION['rand_code']))){
		$Result = ($_POST["Captcha"]==$_SESSION['rand_code'])?1:0;
	}
	return $Result;
}
//Проверить не шифрованные передаваемые параметры управления
private function CheckGets(){

}
//создание кукиса
private function AddCookie($Name,$Value){
	setcookie($Name, $Value, time()+36000);
	return;
}
//воизбежание ошибок: удаление кукиса
private function DeleteCookie($Name){
	setcookie($Name, "", time()-1);
	return;
}

//Сформируем статью-блок: заголовок и основной текст
private function FormMainContentBlock($ContentName,$Head,$Article){
	$Block = "
	<article>
		<div id=\"".$ContentName."Article\">
		<header>
			<div id=\"$ContentName\" style=\"text-align: center;\">
				$Head
			</div>
		</header>
		<div id=\"$ContentName"."Information\" style=\"\">
				$Article
		</div>
		</div>
	</article>
	";
	return $Block;
}
//согласно html5 нам желательно выделить секцию главного контента страницы
private function FormMainContentSection($BlockContent){
	$Block = "
	<section>
		$BlockContent
	</section>";
	return $Block;
}
//тег: Боковая панель (будем скидывать все добро из нее на правую сторону)
//сформируем функцию заталкивающую содержимое в этот тег
private function FormSidePanel($BlockContent){
	$Block = "
	<aside>
		$BlockContent
	</aside>";
	return $Block;
}
//Генератор блоков на сайте
private function FormBlock($BlockName,$BlockContent){
	$Block = "
	<div id=\"$BlockName\" class=\"ContentBlock\" >
		$BlockContent
	</div>";
	return $Block;
}
//распиливаем Jacket на центр, правую и левую колонку 20:60:20
private function FormJacket($Left, $Center, $Right){
$Jacket = "
	<table id=\"Jacket\"  style=\"width: 100%;border-style: ridge;border-width:0px;\">
		<tr valign=\"top\">
			<td  style=\"width: 0%;vertical-align: top;\">
				$Left
			</td>
			<td style=\"width: 100%;vertical-align: top;\">
				$Center
			</td>
			<td style=\"width: 0%;vertical-align: top;\">
				$Right
			</td>
		</tr>
	</table>
";
return $Jacket;
}
//Шапка сайта
private function FormHat($Text){
//Формирует окружающий шапку код
//тег Header нужен по валидатору Html5
	return "
<header>
	<div id=\"Hat\" style=\"text-align: center;\">
		<label class=\"TextLabelHatAndBoots\" >$Text</label>
	</div>
</header>
";
}
//На любые ноги нужны ботинки
private function FormBoots($Copyright,$Year){
	return "		
	<footer>
		 <div id=\"Boots\" style=\"text-align: center\">
				<div class=\"TextLabelHatAndBoots\" >$Copyright $Year</div>
		 </div>
	</footer>";
}
//Формируем таблицу разделяющую части сайта, как шапку, пальто и ботинки
private function FormCostume($Hat, $Jacket, $Boots){
	$Costume = "
<table id=\"Coat\" style=\"width: 100%;border-style: ridge;border-width:0px\" border=\"0\">
	<tr style=\"vertical-align: top;text-align: center;\">
		<td>
			$Hat
		</td>
	</tr>
	<tr style=\"vertical-align: top;text-align: center;\">
		<td>
			$Jacket
		</td>
	</tr>
	<tr style=\"vertical-align: top;text-align: center;\">
		<td>
			$Boots
		</td>
	</tr>
</table>
	";
	return $Costume;
}
//вывод всего на экран
private function Out($text){//вывод на экран (эхо некрасиво)
echo $text;
return;
}
//проверка на наличие SSL
private function CheckSSL(){
//немного кода
	$Result = isset($_SERVER['HTTP_SCHEME']) ? $_SERVER['HTTP_SCHEME'] : (
     ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']
     ) ? 1 : 0);
	return $Result;
}
//Вывести ошибку, что нет SSL (МУХАХАХА)	
private function FormNoSSLError(){
	$Error = "
	<div id=\"Error\" style=\"text-align: center\">
		<label id=\"ErrorHead\">No SSL Error!</label>
		<br>
		<label id=\"ErrorText\">
		Извините, но использовать этот сайт без SSL невозможно.<br> 
		Прошу вас перейти по <a href=".$this->SSLRedirect." >ссылке</a>. 
		</label>
	</div>
	";
	return $Error;
}
//ошибка 404
private function Form404(){
	$Error = "
	<div id=\"Error\" style=\"text-align: center\">
		<label id=\"ErrorHead\">Ошибка 404</label>
		<br>
		<label id=\"ErrorText\">
		Извините, но этой страницы не существует.<br> 
		Прошу вас перейти по <a href=".$this->SSLRedirect." >ссылке</a>.
		</label>
	</div>
	";
	return $Error;
}
//Надо выделить генератор капчи
//для этого весь скрипт придется прогнать еще 1 раз
//надо выделить хрень, которая отделит рисование капчи от вывода веб
private function FormSelector(){
	$this->CheckActions();
	$this->Page = ((isset($_POST["Page"]) ? $_POST["Page"]:"")=="")? 
		(isset($_GET["Page"]) ? $_GET["Page"]:""):$_POST["Page"];
	switch ($this->Page){
	case "Captcha":{
		$this->FormCaptcha();
	}
	break;
	default:{
		$this->Out($this->FormHeader($this->SiteHead));
		$this->Out($this->FormBody());
		$this->Out($this->FormBottom());
	}
	}
	return;
}
//Encode чтобы не потерять символы и не иметь дыр
private function Encode($Str){
	return base64_encode(urlencode($Str));
}
//Decode
private function Decode($Str){
	return urldecode(base64_decode($Str));
}
//Запуск сайта
public function Init(){
	date_default_timezone_set("UTC"); //Задолбал нотис об этом из новой версии Php =\
	$this->FormSelector();
	return;
}
}
//Require_Once ("Website.php");
Session_Start();
$Site = new Website();
$Site->Init(); 
?>