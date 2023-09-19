<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\StudentStage;
use App\Model\Field\StageField;
use App\Model\Field\StageStatus;
use App\Utility\Calc;
use Authentication\IdentityInterface;
use Authorization\Policy\Result;

class StudentStagePolicy
{
    use BasicChecksTrait;

    public function canRegisterEdit(IdentityInterface $user, StudentStage $studentStage)
    {
        if (!$this->stageIs($studentStage, StageField::REGISTER)) {
            return false;
        }

        if ($this->userIsAdmin($user)) {
            return true;
        }

        if ($this->studentIsOwner($user, $studentStage->student_id) && $this->stageStatusIs($studentStage, StageStatus::IN_PROGRESS)) {
            return true;
        }

        return false;
    }

    public function canRegisterValidate(IdentityInterface $user, StudentStage $studentStage)
    {
        if (!$this->stageIs($studentStage, StageField::REGISTER)) {
            return false;
        }

        if ($this->userIsAdmin($user)) {
            return true;
        }

        return false;
    }

    public function canCourseEdit(IdentityInterface $user, StudentStage $studentStage)
    {
        if (!$this->stageIs($studentStage, StageField::COURSE)) {
            return false;
        }

        if ($this->userIsAdmin($user)) {
            return true;
        }

        return false;
    }

    public function canCourseValidate(IdentityInterface $user, StudentStage $studentStage)
    {
        if (!$this->stageIs($studentStage, StageField::COURSE)) {
            return false;
        }

        if ($this->userIsAdmin($user)) {
            return true;
        }

        return false;
    }

    public function canClose(IdentityInterface $user, StudentStage $studentStage): Result
    {
        if ($this->userIsStudent($user) && !$this->studentIsOwner($user, $studentStage->student_id)) {
            return new Result(false, __('You are not the owner of this stage'));
        }

        if ($this->stageIs($studentStage, StageField::TRACKING, StageStatus::IN_PROGRESS)) {
            if (empty($studentStage->student)) {
                return new Result(false, __('student not found'));
            }

            if (($studentStage->student->total_hours ?? 0) < Calc::getTotalHours()) {
                return new Result(false, __('The student has not completed the required hours ({0}h)', Calc::getTotalHours()));
            }

            // @todo verificar que tiene un proyecto por defecto

            return new Result(true);
        }

        return new Result(false, __('stage {0} does not allow closing', $studentStage->stage_label));
    }

    public function canValidate(IdentityInterface $user, StudentStage $studentStage): Result
    {
        if (!$this->userIsAdmin($user)) {
            return new Result(false, __('only admins can validate a stage'));
        }

        if ($this->stageIs($studentStage, StageField::TRACKING, StageStatus::REVIEW)) {
            return new Result(true);
        }

        if ($this->stageIs($studentStage, StageField::ENDING, StageStatus::REVIEW)) {
            return new Result(true);
        }

        return new Result(false, __('stage {0} does not allow validation', $studentStage->stage_label));
    }

    /**
     * @param IdentityInterface $user
     * @param StudentStage $studentStage
     * @return Result
     */
    public function canPrint(IdentityInterface $user, StudentStage $studentStage): Result
    {
        if ($this->userIsStudent($user)) {
            if (!$this->studentIsOwner($user, $studentStage->student_id)) {
                return new Result(false, __('You are not the owner of this stage'));
            }

            if ($this->stageIs($studentStage, StageField::TRACKING, StageStatus::REVIEW) ) {
                // print 007
                return new Result(true);
            }

            if ($this->stageIs($studentStage, StageField::ENDING, StageStatus::REVIEW)) {
                // print 009
                return new Result(true);
            }
        }
        
        if ($this->userIsAdmin($user)) {
            if ($this->stageIs($studentStage, StageField::TRACKING, [StageStatus::REVIEW, StageStatus::SUCCESS])) {
                // print 007
                return new Result(true);
            }

            if ($this->stageIs($studentStage, StageField::ENDING, [StageStatus::REVIEW, StageStatus::SUCCESS])) {
                // print 009
                return new Result(true);
            }
        }

        return new Result(false, __('stage {0} does not allow printing', $studentStage->stage_label));
    }

    /**
     * @param StudentStage $studentStage
     * @param StageField $stageField
     * @param StageStatus|array|null $stageStatus
     * @return boolean
     */
    protected function stageIs(StudentStage $studentStage, StageField $stageField, StageStatus|array $stageStatus = null): bool
    {
        $isStageField = $studentStage->getStage()?->is($stageField) ?? false;

        if (empty($stageStatus)) {
            return $isStageField;
        }

        return $isStageField && $this->stageStatusIs($studentStage, $stageStatus);
    }

    /**
     * @param StudentStage $studentStage
     * @param StageStatus $stageStatus
     * @return boolean
     */
    protected function stageStatusIs(StudentStage $studentStage, StageStatus|array $stageStatus): bool
    {
        return $studentStage->getStatus()?->is($stageStatus) ?? false;
    }
}
