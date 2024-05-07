<?php

namespace Shoptet\Categories\Listener;

use Shoptet\Core\Event;
use Shoptet\Core\EventListener;
use Shoptet\Layout\Helper\FrontendMapHelper;
use Shoptet\Localization\Helper\LanguageHelper;
use Shoptet\Profiles\User;
use Shoptet\Url\Helper\FrontEndHelper;
use Shoptet\Url\Helper\RedirectUrlsHelper;

class Redirector implements EventListener
{
    public function onEvent(Event $event, User $user): void
    {
        if (
            $event->getContext() == 'layout'
            && RedirectUrlsHelper::isPageAutoredirectionOn()
        ) {
            $pageId = $event->getParam('id');
            $frontendLanguages = LanguageHelper::getFrontendLanguages();
            foreach ($frontendLanguages as $language) {
                $page = FrontendMapHelper::getPageById($pageId, $language['code']);
                if ($page) {
                    $categoryUrl = FrontEndHelper::getPageUrl(
                        $page->indexName,
                        $page->pageType,
                        null,
                        $language['code']
                    );
                    RedirectUrlsHelper::deleteRuleByFromUrl($categoryUrl, 1);
                }
            }
        }
    }
}
