<?php
$fontDir = plugin_dir_path(__FILE__) . 'fonts/';
$fontFile = $fontDir . 'ARIAL.TTF';
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta name="viewport">
    <meta charset="utf-8" />
    <title>
    </title>
    <style>
        @font-face {
            font-family: 'Arial';
            src: url('<?php echo $fontFile; ?>') format('truetype');
        }
        body { font-family:"Arial"; font-size:12pt }
        h1, h3, h4, p { margin:0pt }
        table { margin-top:0pt; margin-bottom:0pt }
        h1 { text-align:center; page-break-inside:auto; page-break-after:avoid; font-family:"Arial"; font-size:16pt; font-weight:normal; text-decoration:underline; color:#000000 }
        h3 { page-break-inside:auto; page-break-after:avoid; font-family:"Arial"; font-size:16pt; font-weight:normal; color:#000000 }
        h4 { page-break-inside:auto; page-break-after:avoid; font-family:"Arial"; font-size:12pt; font-weight:normal; font-style:normal; color:#000000 }
        .BalloonText { font-family:Arial; font-size:8pt }
        .EnvelopeAddress { margin-left:144pt; font-size:12pt }
        .NormalWeb { margin-top:5pt; margin-bottom:5pt; font-size:12pt }
        span.BalloonTextChar { font-family:Arial; font-size:8pt }
        span.Hyperlink { text-decoration:underline; color:#0000ff }
        span.Strong { font-weight:bold }
        span.UnresolvedMention { color:#605e5c; background-color:#e1dfdd }
        .footer-content a {
            color: #0000EE;
            text-decoration: none;
        }

    </style>
</head>
<body>
<div>
    <table style="width:540pt; padding:0pt; border-collapse:collapse">
        <tr>
            <td style="width:100%; padding:10pt 5.4pt; vertical-align:top">
                <table>
                    <tr>
                        <td style="width:100%; vertical-align:top;">
                            <p>
                                <span style=""><img src="{{PLACEHOLDER_SRC}}" width="323" height="71" alt="" style="" /></span>&#xa0;
                            </p>
                            <div style="margin-left:12.6pt; text-align:left; margin-top:12pt;">
                                <p style="font-size:10pt; ">
                                    <span style="font-family: Arial; ">310, 4 rue Cataraqui Street, Kingston ON K7K 1Z7</span>
                                </p>
                                <p style=" font-size:10pt">
                                    <span style="font-family: Arial; ">Tel/Tél: 613-531-2661 | <a href="mailto:membership@ches.org">membership@ches.org</a> | <a href="https://ches.org">www.ches.org</a></span>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>