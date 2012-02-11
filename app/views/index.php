<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Omniapp PHP Framework</title>

    <meta content="A blazing fast Model View Controller framework for the PHP language." name="description" />

    <style type="text/css">
        body {
            border-top: 20px solid #eee;
            background: #fff;
            font: 13px/19px verdana;
            text-align: left;
            margin: 0;
            padding:0;
            color: #777;
        }

        a { text-decoration: none; color: #1f6397; }
        a:hover { text-decoration: underline; }
        b { color: #000; }
        h2 {font: 28px/36px times new roman; color: #881a3c;}
        em {background: #fffdde; }
        #main {
            margin: 0 auto 100px auto;
            padding: 0;
            width: 600px;
        }

        code {
            white-space:pre;
            border: 1px solid #ddd;
            margin: 20px;
            display: block;
            padding: 10px;
        }

        h1 {
            font-size: 400%;
            line-height: 100%;
            font-weight: normal;
            font-family: times new roman;
            text-align: center;
            color: #000;
        }


    </style>

</head>
<body>

<div id="main">

    <h1><?php echo __('Welcome to OmniApp!'); ?><sub style="font-size: 12px;"> v<?php echo $version; ?></sub></h1>
    <b>A blazing fast Model View Controller framework</b>

    <p>Omniapp is a module-based MVC that is currently in active development.
        Featuring a full ORM, database library, migrations, administration scaffolding,
        and many other small and easy to understand PHP libraries.

    <p>Omniapp is licenced under the <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
        so you can use it for any personal or corporate projects free of charge.</p>

    <h2>Download</h2>
    <p>Omniapp is hosted over at <a href="http://github.com/hfcorriez/omniapp">github</a> where you can easily download
        the system in a zip or tar archive. You can even geek out and fork your own branch.</p>
    <p>The development branch contains the latest system changes but may not be stable.</p>

    <h2>Requirements</h2>

    <ul>
        <li>PHP 5.3+</li>
        <li>Nginx 0.7.27+ (Or Apache with mod_rewrite)</li>
    </ul>

    <h2>Setup</h2>

    <p>Each folder in the root directory is a module that can be studied. The "system" module is special
        and contains some of the core system classes. Do not edit it.</p>

    <p>Rename the <em>config.sample.php</em> to <em>config.php</em> to begin
        using the system. You will find the example module to contain all the basic
        examples.</p>

    <br /><br />
    <center><b>Thank you for trying out Omniapp</b><br />
        <a href="http://omniapp.org">Omniapp</a> &copy 2011 - 2012
</div>
</body>
</html>