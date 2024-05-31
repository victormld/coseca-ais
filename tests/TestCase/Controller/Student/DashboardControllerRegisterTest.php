<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Student;

use App\Test\Traits\CommonTestTrait;
use App\Model\Field\AdscriptionStatus;
use App\Model\Field\StageField;
use App\Model\Field\StageStatus;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\I18n\FrozenDate;


/**
 * App\Controller\Student\DashboardController Test Case
 *
 * @uses \App\Controller\Student\DashboardController
 */
class DashboardControllerRegisterTest extends TestCase
{


    use IntegrationTestTrait;
    use CommonTestTrait;


    protected $program;
    protected $tenant;
    protected $student;
    protected $institution;
    protected $tutor;
    protected $user;
    protected $lapse_id;
    protected $lapse;
    protected $alertMessage = 'Comuniquese con la coordinación de servicio comunitario para mas información';


    protected function setUp(): void
    {
        parent::setUp();

        $this->program = $this->createProgram()->persist();
        $this->tenant = Hash::get($this->program, 'tenants.0');
        $this->student = $this->createStudent(['tenant_id' => $this->tenant->id])->persist();
        $this->institution = $this->createInstitution(['tenant_id' => $this->tenant->id])->persist();
        $this->tutor = $this->createTutor(['tenant_id' => $this->tenant->id])->persist();
        $this->user = $this->setAuthSession(Hash::get($this->student, 'app_user'));
        $this->lapse = Hash::get($this->program, 'tenants.0.lapses.0');
        $this->lapse_id = $this->lapse->id;
        $this->setDefaultLapseDates($this->lapse_id);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->program);
        unset($this->tenant);
        unset($this->student);
        unset($this->institution);
        unset($this->tutor);
        unset($this->user);
        unset($this->lapse_id);
    }

    public function testRegisterCardStatusInProgress(): void
        {
            $lapse_id = $this->lapse_id;

            $this->createStudentStage([
                'student_id' => $this->student->id,
                'stage' => StageField::REGISTER->value,
                'status' => StageStatus::IN_PROGRESS->value,
            ])->persist();

            // whitout lapse dates
            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains('No existe fecha de registro');
            $this->assertResponseContains($this->alertMessage);

            $lapseDate = $this->getRecordByOptions('LapseDates', [
                'lapse_id' => $lapse_id,
                'stage' => StageField::REGISTER->value,
            ]);

            // with lapse dates in pass
            $start_date = FrozenDate::now()->subDays(4);
            $end_date = FrozenDate::now()->subDays(3);
            $this->updateRecord($lapseDate, compact('start_date', 'end_date'));
            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains('Ya pasó el período de registro');
            $this->assertResponseContains($this->alertMessage);

            // with lapse dates in future
            $start_date = FrozenDate::now()->addDays(3);
            $end_date = FrozenDate::now()->addDays(4);
            $this->updateRecord($lapseDate, compact('start_date', 'end_date'));
            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains(__('Fecha de registro: {0}', $lapseDate->show_dates));
            $this->assertResponseContains($this->alertMessage);

            // with lapse dates in progress
            $start_date = FrozenDate::now()->subDays(1);
            $end_date = FrozenDate::now()->addDays(1);
            $this->updateRecord($lapseDate, compact('start_date', 'end_date'));
            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains('Formulario de registro');
        }

        public function testRegisterCardStatusReview(): void
        {
            $this->createStudentStage([
                'student_id' => $this->student->id,
                'stage' => StageField::REGISTER->value,
                'status' => StageStatus::REVIEW->value,
            ])->persist();
            

            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains('En espera de revisión de documentos.');
            $this->assertResponseContains($this->alertMessage);
        }

       public function testRegisterCardStatusSuccess(): void
        {
            $this->createStudentStage([
                'student_id' => $this->student->id,
                'stage' => StageField::REGISTER->value,
                'status' => StageStatus::SUCCESS->value,
            ])->persist();

            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains(Hash::get($this->user, 'dni'));
            $this->assertResponseContains(Hash::get($this->user, 'first_name'));
            $this->assertResponseContains(Hash::get($this->user, 'last_name'));
            $this->assertResponseContains(Hash::get($this->user, 'email'));
            //$this->assertResponseContains($this->program->name . ' | ' . $this->program->tenants[0]->location->name);
            //$this->assertResponseContains(Hash::get($this->student, 'student_data.phone'));
        }

        public function testRegisterCardOtherStatuses(): void
        {
            $stageRegistry = $this->createStudentStage([
                'student_id' => $this->student->id,
                'stage' => StageField::REGISTER->value,
                'status' => StageStatus::WAITING->value,
            ])->persist();

            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains('Sin información a mostrar');
            $this->assertResponseContains($this->alertMessage);

            $this->updateRecord($stageRegistry, ['status' => StageStatus::FAILED->value]);
            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains('Sin información a mostrar');
            $this->assertResponseContains($this->alertMessage);

            $this->updateRecord($stageRegistry, ['status' => StageStatus::LOCKED->value]);
            $this->get('/student');
            $this->assertResponseOk();
            $this->assertResponseContains('Sin información a mostrar');
            $this->assertResponseContains($this->alertMessage);
        } 
    }