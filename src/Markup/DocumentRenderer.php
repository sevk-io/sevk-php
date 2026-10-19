<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class DocumentRenderer
{
    /**
     * @param array{
     *     title: string,
     *     previewText: string,
     *     styles: string,
     *     fonts: list<array{id: string, name: string, url: string}>,
     *     lang: string,
     *     dir: string
     * } $head
     * @param list<string> $gapRules
     */
    public function render(string $content, array $head, array $gapRules): string
    {
        $title = $head['title'] !== '' ? "<title>{$head['title']}</title>" : '';

        $fontLinks = implode("\n", array_map(
            static fn (array $font): string => sprintf('<link href="%s" rel="stylesheet" type="text/css" />', $font['url']),
            $head['fonts']
        ));

        $styles = $head['styles'] !== '' ? "<style type=\"text/css\">{$head['styles']}</style>" : '';
        $gapStyles = $gapRules === []
            ? ''
            : '<style type="text/css">@media only screen and (max-width:479px){'
                . implode('', $gapRules)
                . '}</style>';

        $preview = '';

        if ($head['previewText'] !== '') {
            $preview = sprintf(
                '<div style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">%s</div>',
                $head['previewText']
            );
        }

        return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="{$head['lang']}" dir="{$head['dir']}" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="x-apple-disable-message-reformatting"/>
<meta content="IE=edge" http-equiv="X-UA-Compatible"/>
<meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no"/>
<!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:AllowPNG/>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
<style type="text/css">
#outlook a { padding: 0; }
body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
.sevk-row-table { border-collapse: separate !important; }
img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
@media only screen and (max-width: 479px) {
  .sevk-row-table { width: 100% !important; }
  .sevk-column { display: block !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
}
</style>
{$gapStyles}
{$title}
{$fontLinks}
{$styles}
</head>
<body style="margin:0;padding:0;word-spacing:normal;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;font-family:ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
<div aria-roledescription="email" role="article">
{$preview}
{$content}
</div>
</body>
</html>
HTML;
    }
}
