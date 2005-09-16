<?php
session_start();
print "
<HTML>
<HEAD>
<META NAME=\"Content-Type\" Content=\"text/html; charset=iso-8859-1\">

<TITLE>Search_Mnogosearch Examples</TITLE>
<link rel=\"stylesheet\" href=\"style.css\">
</head>
<body bgcolor=\"#ffffff\">";

print "<a href=\"".$_SERVER['SCRIPT_NAME']."?demo=1\">Example with default Renderer</a><br/>\n";
print "<a href=\"".$_SERVER['SCRIPT_NAME']."?demo=2\">Example with Sigma Renderer</a><br/>\n";
print "<a href=\"".$_SERVER['SCRIPT_NAME']."?demo=3\">Example with IT Renderer</a><br/>\n";
print "<a href=\"http://ohrwurm.net\">german Jobsearch Engine</a><br/>\n";

if (isset($_GET['demo'])) {
    $demo = $_GET['demo'];
    $_SESSION['demo'] = $demo;
} elseif (isset($_SESSION['demo'])) {
    $demo = $_SESSION['demo'];
}
/**
*/

if ($demo==1) {
    include_once('search-example.php');
    if (!isset($_GET['show'])) {
        print "<a href=\"".$_SERVER['SCRIPT_NAME']."?demo=1&amp;show=1\">show source</a><br/>\n";
    } else {
        print "<br><b>Source of search-example.php</b><table width=\"100%\" class=\"source\"><tr><td width=\"100%\" valign=\"top\">";
        highlight_file('search-example.php');
        print "</td></tr></table>";
    }
}
if ($demo==2) {
    include_once('sigma-example.php');
    if (!isset($_GET['show'])) {
        print "<a href=\"".$_SERVER['SCRIPT_NAME']."?demo=2&amp;show=1\">show source</a><br/>\n";
    } else { 
        print "<br><b>Source of sigma-example.php</b><table width=\"100%\" class=\"source\"><tr><td width=\"100%\" valign=\"top\">";
        highlight_file('sigma-example.php');
        print "</td></tr></table>";
    }
}
if ($demo==3) {
    include_once('it-example.php');
    if (!isset($_GET['show'])) {
        print "<a href=\"".$_SERVER['SCRIPT_NAME']."?demo=3&amp;show=1\">show source</a><br/>\n";
    } else { 
        print "<br><b>Source of it-example.php</b><table width=\"100%\" class=\"source\"><tr><td width=\"100%\" valign=\"top\">";
        highlight_file('it-example.php');
        print "</td></tr></table>";
    }
}



print "</body></html>";
?>
