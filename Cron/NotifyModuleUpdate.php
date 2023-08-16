<?php

namespace Flekto\Postcode\Cron;

use Psr\Log\LoggerInterface;
use Flekto\Postcode\Helper\Data as DataHelper;
use Flekto\Postcode\Model\UpdateNotification\UpdateNotifier;

class NotifyModuleUpdate
{
    protected $_logger;
    protected $_dataHelper;
    protected $_updateNotifier;

    /**
     * Constructor
     *
     * @access public
     * @param LoggerInterface $logger
     * @param DataHelper $dataHelper
     * @param UpdateNotifier $updateNotifier
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        DataHelper $dataHelper,
        UpdateNotifier $updateNotifier,
    ) {
        $this->_logger = $logger;
        $this->_dataHelper = $dataHelper;
        $this->_updateNotifier = $updateNotifier;
    }

    /**
     * Run cron job.
     *
     * @access public
     * @return void
     */
    public function execute(): void
    {
        $moduleInfo = $this->_dataHelper->getModuleInfo();
        if (($moduleInfo['has_update'] ?? false)
            && $this->_updateNotifier->notifyVersion($moduleInfo['latest_version'])
        ) {
            $this->_logger->info(__('Added notification for Postcode.eu Address API %1 update.', $moduleInfo['latest_version']));
        }
    }
}
