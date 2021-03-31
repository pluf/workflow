<?php
namespace Pluf\Workflow;

use Pluf\Workflow\Conditions\Always;
use Pluf\Workflow\Conditions\Never;
use Pluf\Workflow\Conditions\AndCondition;
use Pluf\Workflow\Conditions\OrCondition;
use Pluf\Workflow\Conditions\NotCondition;
use Pluf\Workflow\Conditions\XorCondition;

class Conditions
{
    public static function isSatified(Condition $condition, $context) : bool{
        return $condition!=null && $context!=null && $condition->isSatisfied($context);
    }
    
    public static function isNotSatified(Condition $condition, $context): bool {
        return $condition==null || $context==null || !$condition->isSatisfied($context);
    }
    
    public static function always(): Always  {
        return new Always();
    }
    
    public static function never(): Never {
        return new Never();
    }
    
    public static function and(Condition $first, Condition $second): AndCondition {
        return new AndCondition($first, $second);
    }
    
//     public static <C> Condition<C> and(final List<Condition<C>> conditions) {
//         return new Condition<C>() {
//             @Override
//             public boolean isSatisfied(C context) {
//                 for (Condition<C> condition : conditions) {
//                     if (!condition.isSatisfied(context)) {
//                         return false;
//                     }
//                 }
//                 return true;
//             }
//             @Override
//             public String name() {
//                 String name = null;
//                 for(Condition<C> c : conditions) {
//                     if(name==null)
//                         name=c.name();
//                         else
//                             name = name+"And"+c.name();
//                 }
//                 return name;
//             }
//         };
//     }
    
    public static function or(Condition $first, Condition $second): Condition {
        return new OrCondition($first, $second);
    }
    
//     public static <C> Condition<C> or(final List<Condition<C>> conditions) {
//         return new Condition<C>() {
//             @Override
//             public boolean isSatisfied(C context) {
//                 for (Condition<C> condition : conditions) {
//                     if (condition.isSatisfied(context)) {
//                         return true;
//                     }
//                 }
//                 return false;
//             }
            
//             @Override
//             public String name() {
//                 String name = null;
//                 for(Condition<C> c : conditions) {
//                     if(name==null)
//                         name=c.name();
//                         else
//                             name = name+"Or"+c.name();
//                 }
//                 return name;
//             }
//         };
//     }
    
    public static function not(Condition $condition) : Condition{
        return new NotCondition($condition);
    }
    
    public static function xor(Condition $first, Condition $second) :Condition{
        return new XorCondition($first, $second);
    }
}

