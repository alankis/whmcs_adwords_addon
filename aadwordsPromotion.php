<?php
 
use WHMCS\View\Menu\Item;
 
add_hook('ClientAreaHomepagePanels', 1, function (Item $homePagePanels)
{
    $thankYouMessage = <<<EOT
<p>Infonet i Google Vam poklanjaju promotivne kupone za <strong>Google AdWords</strong>.</p>
EOT;
 
    // Add a homepage panel with a link to a free month promo and mode it to the
    // front of the panel list.
    $homePagePanels->addChild('thanks', array(
        'label' => 'AdWords promotivna ponuda',
        'icon' => 'fa-thumbs-up',
        'order' => 20,
        'extras' => array(
            'color' => 'midnight-blue',
            'btn-link' => 'adwords.php',
            'btn-text' => 'Preuzmi kupon!',
            'btn-icon' => 'fa-arrow-right',
        ),
        'bodyHtml' => $thankYouMessage,
        //'footerHtml' => 'Act fast! This offer expires soon!',
    ));
});
