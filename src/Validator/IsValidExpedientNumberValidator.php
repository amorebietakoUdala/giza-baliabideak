<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Validator;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Description of ExpCodeValidator.
 * Checks if it a valida Expedient Code
 *
 * @author ibilbao
 */

/**
 * @Annotation
 */
class IsValidExpedientNumberValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        /** @var $constraint \App\Validator\IsValidExpedientNumber */

        if (null === $value || '' === $value) {
            return;
        }
        $return = $this->validateExpCode($value);
        if ($return <= 0) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
     }

     private function validateExpCode(string $expCode): bool 
     {
        $pattern = '/^(\d+)\/(\d+)$/';
        $matches = preg_match($pattern, $expCode, $matchesArray);
        if (!$matches) {
            return false;
        }
        if (is_int($matchesArray[1])) {
            return false;
        }
        if (is_int($matchesArray[2])) {
            return false;
        }
        if ( intVal($matchesArray[2]) < 1950 || intVal($matchesArray[2]) > 3000 ) {
            return false;
        }
        return true;
    } 

}
