<?php
namespace Pluf\Workflow;

use Pluf\Workflow\Component\IdProvider;

class StateMachineConfiguration
{

    private bool $autoStartEnabled = true;

    private bool $autoTerminateEnabled = true;

    private bool $dataIsolateEnabled = false;

    private bool $debugModeEnabled = false;

    private bool $delegatorModeEnabled = false;

    private ?IdProvider $idProvider = null;

    private static $instance;

    public static function getInstance(): StateMachineConfiguration
    {
        if (! self::$instance) {
            self::$instance = self::create();
        }
        return self::$instance;
    }

    public static function setInstance(StateMachineConfiguration $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Creates default configuration
     */
    public static function create(): StateMachineConfiguration
    {
        return new StateMachineConfiguration();
    }

    public function __construct(bool $autoStartEnabled = true, bool $autoTerminateEnabled = true, bool $dataIsolateEnabled = false, bool $debugModeEnabled = false, bool $delegatorModeEnabled = false, ?IdProvider $idProvider = null)
    {
        $this->autoStartEnabled = $autoStartEnabled;
        $this->autoTerminateEnabled = $autoTerminateEnabled;
        $this->dataIsolateEnabled = $dataIsolateEnabled;
        $this->debugModeEnabled = $debugModeEnabled;
        $this->delegatorModeEnabled = $delegatorModeEnabled;
        $this->idProvider = $idProvider;
    }

    public function isAutoStartEnabled(): bool
    {
        return $this->autoStartEnabled;
    }

    public function enableAutoStart(bool $autoStartEnabled): self
    {
        $this->autoStartEnabled = $autoStartEnabled;
        return $this;
    }

    public function isAutoTerminateEnabled(): bool
    {
        return $this->autoTerminateEnabled;
    }

    public function enableAutoTerminate(bool $autoTerminateEnabled): self
    {
        $this->autoTerminateEnabled = $autoTerminateEnabled;
        return $this;
    }

    public function isDataIsolateEnabled(): bool
    {
        return $this->dataIsolateEnabled;
    }

    public function enableDataIsolate(bool $dataIsolateEnabled): self
    {
        $this->isDataIsolateEnabled = $dataIsolateEnabled;
        return $this;
    }

    public function getIdProvider(): IdProvider
    {
        return $this->idProvider;
    }

    public function setIdProvider(IdProvider $idProvider): self
    {
        $this->idProvider = $idProvider;
        return $this;
    }

    public function isDebugModeEnabled(): bool
    {
        return $this->debugModeEnabled;
    }

    public function enableDebugMode(bool $ebugModeEnabled): self
    {
        $this->ebugModeEnabled = $ebugModeEnabled;
        return $this;
    }

    public function isDelegatorModeEnabled(): bool
    {
        return $this->delegatorModeEnabled;
    }

    public function enableDelegatorMode(bool $delegatorModeEnabled): self
    {
        $this->delegatorModeEnabled = $delegatorModeEnabled;
        return $this;
    }
}

