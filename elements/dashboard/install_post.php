<?php

use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;

defined('C5_EXECUTE') or die('Access Denied.');

$jobLink = t('Update Cloudflare IPs');
$page = Page::getByPath('/dashboard/system/optimization/jobs');
if ($page && !$page->isError()) {
    $checker = new Checker($page);
    if ($checker->canRead()) {
        $jobLink = sprintf('<a href="%s">%s</a>', URL::to('/dashboard/system/optimization/jobs'), $jobLink);
    }
}
?>
<p><?= t('The package has been installed, but the list of Cloudfare IP addresses is not yet configured.') ?></p>
<p><?= t(
    'To configure it, please run the %s CLI command, or the %s authomated job.',
    '<code>cf:ip:update</code>',
    $jobLink
) ?></p>
