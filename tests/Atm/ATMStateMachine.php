<?php
namespace Pluf\Tests\Atm;

/**
 * ATM State machin implementation
 *
 * A state machine implementation consist of several action to use in state machin transitions
 *
 * @author maso
 */
class ATMStateMachine
{

    private string $log = '';

    public function entryIdle($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("entryIdle");
    }

    public function exitIdle($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("exitIdle");
    }

    public function transitFromIdleToLoadingOnConnected($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromIdleToLoadingOnConnected");
    }

    public function entryLoading($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("entryLoading");
    }

    public function exitLoading($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("exitLoading");
    }

    public function transitFromLoadingToInServiceOnLoadSuccess($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromLoadingToInServiceOnLoadSuccess");
    }

    public function transitFromLoadingToOutOfServiceOnLoadFail($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromLoadingToOutOfServiceOnLoadFail");
    }

    public function transitFromLoadingToDisconnectedOnConnectionClosed($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromLoadingToDisconnectedOnConnectionClosed");
    }

    public function entryOutOfService($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("entryOutOfService");
    }

    public function transitFromOutOfServiceToDisconnectedOnConnectionLost($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromOutOfServiceToDisconnectedOnConnectionLost");
    }

    public function transitFromOutOfServiceToInServiceOnStartup($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromOutOfServiceToInServiceOnStartup");
    }

    public function exitOutOfService($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("exitOutOfService");
    }

    public function entryDisconnected($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("entryDisconnected");
    }

    public function transitFromDisconnectedToInServiceOnConnectionRestored($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromDisconnectedToInServiceOnConnectionRestored");
    }

    public function exitDisconnected($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("exitDisconnected");
    }

    public function entryInService($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("entryInService");
    }

    public function transitFromInServiceToOutOfServiceOnShutdown($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromInServiceToOutOfServiceOnShutdown");
    }

    public function transitFromInServiceToDisconnectedOnConnectionLost($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("transitFromInServiceToDisconnectedOnConnectionLost");
    }

    public function exitInService($from, $to, $event)
    {
        $this->addOptionalDot();
        $this->logger("exitInService");
    }

    private function addOptionalDot()
    {
        $this->log = '.' . $this->log;
    }

    private function logger($log)
    {
        $this->log = $log . $this->log;
    }

    public function consumeLog()
    {
        $log = $this->log;
        $this->log = '';
        return $log;
    }
}