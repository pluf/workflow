<?php
// namespace Pluf\Test;

// use Pluf\Workflow\Attributes\State;
// use Pluf\Workflow\Attributes\Transitions;
// use Pluf\Workflow\Attributes\Transit;
// use Pluf\Workflow\Attributes\States;
// use Pluf\Workflow\Attributes\StateMachineParameters;
                                                                                
// #[
//     StateMachineParameters(
//         stateType: 'string',
//         eventType: 'string',
//         contextType: PaymentGate::class
//     ),
//     States([
//         new State(
//             name: 'Locked'
//         ),
//         new State(
//             name: 'Unlocked'
//         )
//     ]),
//     Transitions([
//         new Transit(
//             from: 'Locked',
//             to: 'Unlocked',
//             on: 'coin',
//             callMethod: 'addCoin'
//         ),
//         new Transit(
//             from: 'Locked',
//             to: 'Locked',
//             on: 'push',
//             callMethod: 'beep'
//         ),
//         new Transit(
//             from: 'Unlocked',
//             to: 'Locked',
//             on: 'push',
//             callMethod: 'addPerson'
//         ),
//         new Transit(
//             from: 'Unlocked',
//             to: 'Unlocked',
//             on: 'coin',
//             callMethod: 'beep'
//         ),
//     ])
// ]
// class PaymentGate
// {

//     public string $state = 'locked';

//     public int $value = 0;

//     public int $people = 0;

//     public function addCoin(int $coines = 1)
//     {
//         $this->value += $coines;
//     }

//     public function addPerson(int $count = 1)
//     {
//         $this->people += $count;
//     }

//     public function beep()
//     {
//         echo "Beep!!!";
//     }
// }

