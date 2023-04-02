<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Student;

use App\Model\Entity\Student;
use App\Model\Field\AdscriptionStatus;
use App\Model\Field\StageField;
use App\Model\Field\StageStatus;
use App\Model\Field\StudentType;
use App\Model\Field\UserRole;
use App\Test\Factory\CreateDataTrait;
use App\Test\Factory\InstitutionFactory;
use App\Test\Factory\TutorFactory;
use Cake\I18n\FrozenDate;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

/**
 * App\Controller\Student\StagesController Test Case
 *
 * @uses \App\Controller\Student\StagesController
 */
class StagesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use CreateDataTrait;

    protected $program;
    protected $tenant_id;
    protected $user;
    protected $lapse_id;
    protected $tutors;
    protected $institution;

    protected $alertMessage = 'Comuniquese con la coordinación de servicio comunitario para mas información';

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->program = $this->createProgram()->persist();
        $this->user = $this->createUser(['role' => UserRole::STUDENT->value])->persist();
        $this->tenant_id = Hash::get($this->program, 'tenants.0.id');
        $this->lapse_id = Hash::get($this->program, 'tenants.0.lapses.0.id');
        $this->setDefaultLapseDates($this->lapse_id);

        $this->tutors = TutorFactory::make([
            'tenant_id' => $this->tenant_id,
        ], 5)->persist();

        $this->institution = InstitutionFactory::make([
            'tenant_id' => $this->tenant_id,
        ])
            ->with('InstitutionProjects', [], 5)
            ->persist();


        $this->session(['Auth' => $this->user]);
    }

    protected function tearDown(): void
    {
        unset($this->program);
        unset($this->user);
        unset($this->lapse_id);
        unset($this->tenant_id);
        unset($this->tutors);
        unset($this->institution);

        parent::tearDown();
    }

    protected function createRegularStudent(): Student
    {
        $interest_area_key = rand(0, count($this->program->interest_areas) - 1);

        return $this->createStudent([
            'type' => StudentType::REGULAR->value,
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant_id,
            'lapse_id' => $this->lapse_id,
        ])
            ->with('StudentData', [
                'interest_area_id' => $this->program->interest_areas[$interest_area_key]->id,
            ])
            ->persist();
    }

    public function testStudentTypeRegular(): void
    {
        $student = $this->createRegularStudent();

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Registro');
        $this->assertResponseContains('Taller');
        $this->assertResponseContains('Adscripción');
        $this->assertResponseContains('Seguimiento');
        $this->assertResponseContains('Resultados');
        $this->assertResponseContains('Conclusión');
        $this->assertResponseNotContains('Convalidación');

        $this->updateRecord($student, ['type' => StudentType::VALIDATED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Registro');
        $this->assertResponseNotContains('Taller');
        $this->assertResponseNotContains('Adscripción');
        $this->assertResponseNotContains('Seguimiento');
        $this->assertResponseNotContains('Resultados');
        $this->assertResponseNotContains('Conclusión');
        $this->assertResponseContains('Convalidación');
    }

    public function testRegisterCardStatusInProgress(): void
    {
        $student = $this->createRegularStudent();
        $lapse_id = $this->lapse_id;

        $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::REGISTER->value,
            'status' => StageStatus::IN_PROGRESS->value,
        ]);

        $lapseDate = $this->getRecordByOptions('LapseDates', [
            'lapse_id' => $lapse_id,
            'stage' => StageField::REGISTER->value,
        ]);

        // whitout lapse dates
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('No existe fecha de registro');
        $this->assertResponseContains($this->alertMessage);

        // with lapse dates in pass
        $start_date = FrozenDate::now()->subDays(4);
        $end_date = FrozenDate::now()->subDays(3);
        $this->updateRecord($lapseDate, compact('start_date', 'end_date'));
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Ya pasó el período de registro');
        $this->assertResponseContains($this->alertMessage);

        // with lapse dates in future
        $start_date = FrozenDate::now()->addDays(3);
        $end_date = FrozenDate::now()->addDays(4);
        $this->updateRecord($lapseDate, compact('start_date', 'end_date'));
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains(__('Fecha de registro: {0}', $lapseDate->show_dates));
        $this->assertResponseContains($this->alertMessage);

        // with lapse dates in progress
        $start_date = FrozenDate::now()->subDays(1);
        $end_date = FrozenDate::now()->addDays(1);
        $this->updateRecord($lapseDate, compact('start_date', 'end_date'));
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Formulario de registro');
    }

    public function testRegisterCardStatusReview(): void
    {
        $student = $this->createRegularStudent();
        $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::REGISTER->value,
            'status' => StageStatus::REVIEW->value,
        ]);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('En espera de revisión de documentos.');
        $this->assertResponseContains($this->alertMessage);
    }

    public function testRegisterCardStatusSuccess(): void
    {
        $student = $this->createRegularStudent();
        $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::REGISTER->value,
            'status' => StageStatus::SUCCESS->value,
        ]);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains(Hash::get($this->user, 'dni'));
        $this->assertResponseContains(Hash::get($this->user, 'first_name'));
        $this->assertResponseContains(Hash::get($this->user, 'last_name'));
        $this->assertResponseContains(Hash::get($this->user, 'email'));
        $this->assertResponseContains($this->program->name . ', ' . $this->program->tenants[0]->name);
        $this->assertResponseContains(Hash::get($student, 'student_data.phone'));
    }

    public function testRegisterCardOtherStatuses(): void
    {
        $student = $this->createRegularStudent();

        $stageRegistry = $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::REGISTER->value,
            'status' => StageStatus::WAITING->value,
        ]);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);

        $this->updateRecord($stageRegistry, ['status' => StageStatus::FAILED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);

        $this->updateRecord($stageRegistry, ['status' => StageStatus::LOCKED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);
    }

    public function testCourseCardStatusWaiting(): void
    {
        $lapse_id = $this->lapse_id;
        $student = $this->createRegularStudent();
        $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::COURSE->value,
            'status' => StageStatus::WAITING->value,
        ]);

        $lapseDate = $this->getRecordByOptions('LapseDates', [
            'lapse_id' => $lapse_id,
            'stage' => StageField::COURSE->value,
        ]);

        // whitout lapse dates
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('En espera de la fecha del taller de Servicio Comunitario');
        $this->assertResponseContains($this->alertMessage);

        // with lapse dates in pass
        $start_date = FrozenDate::now()->subDays(4);
        $this->updateRecord($lapseDate, compact('start_date'));
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Fecha del taller de servicio comunitario: ' . $start_date . ' <small>(Caducado)</small>');
        $this->assertResponseContains($this->alertMessage);

        // with lapse dates in future
        $start_date = FrozenDate::now()->addDays(4);
        $this->updateRecord($lapseDate, compact('start_date'));
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Fecha del taller de servicio comunitario: ' . $start_date . ' <small>(Pendiente)</small>');
        $this->assertResponseContains($this->alertMessage);

        // with lapse dates in progress
        $start_date = FrozenDate::now();
        $this->updateRecord($lapseDate, compact('start_date'));
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Fecha del taller de servicio comunitario: ' . $start_date . ' <small>(En Progreso)</small>');
        $this->assertResponseContains($this->alertMessage);
    }

    public function testCourseCardStatusSuccess(): void
    {
        $student = $this->createRegularStudent();
        $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::COURSE->value,
            'status' => StageStatus::SUCCESS->value,
        ]);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);

        $courseDate = FrozenDate::now();
        $this->addRecord('StudentCourses', [
            'student_id' => $student->id,
            'date' => $courseDate,
            'comment' => 'Comentario de prueba',
        ]);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<strong>Fecha del Taller: </strong>' . $courseDate);
        $this->assertResponseContains('Comentario de prueba');
    }

    public function testCourseCardOtherStatuses(): void
    {
        $student = $this->createRegularStudent();
        $stage = $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::COURSE->value,
            'status' => StageStatus::IN_PROGRESS->value,
        ]);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);

        $this->updateRecord($stage, ['status' => StageStatus::REVIEW->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);

        $this->updateRecord($stage, ['status' => StageStatus::FAILED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);

        $this->updateRecord($stage, ['status' => StageStatus::LOCKED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('Sin información a mostrar');
        $this->assertResponseContains($this->alertMessage);
    }

    public function testAdscriptionCardStatusWaiting(): void
    {
        $student = $this->createRegularStudent();
        $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::ADSCRIPTION->value,
            'status' => StageStatus::WAITING->value,
        ]);

        // whitout lapse dates
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('El estudiante no tiene proyectos adscritos');
        $this->assertResponseContains($this->alertMessage);

        $project = Hash::get($this->institution, 'institution_projects.0');
        $tutor = Hash::get($this->tutors, '0');
        $adscription = $this->addRecord('StudentAdscriptions', [
            'student_id' => $student->id,
            'institution_project_id' => $project->id,
            'tutor_id' => $tutor->id,
            'status' => AdscriptionStatus::PENDING->value,
        ]);
        $project_label_name = __('{0}: {1}', $this->institution->name, $project->name);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains($project_label_name);
        $this->assertResponseContains('<span class="badge badge-warning ">Pendiente</span>');
        $this->assertResponseContains('Planilla de Adscripción');
        $this->assertResponseContains('<dd class="col-sm-8">' . $this->institution->name . '</dd>');
        $this->assertResponseContains('<dd class="col-sm-8">' . $this->institution->contact_person . '</dd>');
        $this->assertResponseContains('<dd class="col-sm-8">' . $this->institution->contact_phone . '</dd>');
        $this->assertResponseContains('<dd class="col-sm-8">' . $this->institution->contact_email . '</dd>');
        $this->assertResponseContains('<dd class="col-sm-8">' . $project->name . '</dd>');
        $this->assertResponseContains('<dd class="col-sm-8">' . $tutor->name . '</dd>');
        $this->assertResponseContains('<dd class="col-sm-8">' . $tutor->phone . '</dd>');
        $this->assertResponseContains('<dd class="col-sm-8">' . $tutor->email . '</dd>');

        $this->updateRecord($adscription, ['status' => AdscriptionStatus::OPEN->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains($project_label_name);
        $this->assertResponseContains('<span class="badge badge-success ">Abierto</span>');
        $this->assertResponseNotContains('Planilla de Adscripción');

        $this->updateRecord($adscription, ['status' => AdscriptionStatus::CLOSED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains($project_label_name);
        $this->assertResponseContains('<span class="badge badge-danger ">Cerrado</span>');
        $this->assertResponseNotContains('Planilla de Adscripción');

        $this->updateRecord($adscription, ['status' => AdscriptionStatus::VALIDATED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains($project_label_name);
        $this->assertResponseContains('<span class="badge badge-primary ">Validado</span>');
        $this->assertResponseNotContains('Planilla de Adscripción');

        $this->updateRecord($adscription, ['status' => AdscriptionStatus::CANCELLED->value]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseNotContains($project_label_name);
        $this->assertResponseNotContains($project->name);
    }

    public function testTrackingCardStatusInProgress(): void
    {
        $student = $this->createRegularStudent();
        $this->addRecord('StudentStages', [
            'student_id' => $student->id,
            'stage' => StageField::TRACKING->value,
            'status' => StageStatus::IN_PROGRESS->value,
        ]);

        // whitout lapse dates
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('El estudiante no tiene proyectos adscritos');
        $this->assertResponseContains($this->alertMessage);

        $project = Hash::get($this->institution, 'institution_projects.0');
        $tutor = Hash::get($this->tutors, '0');
        $adscription = $this->addRecord('StudentAdscriptions', [
            'student_id' => $student->id,
            'institution_project_id' => $project->id,
            'tutor_id' => $tutor->id,
            'status' => AdscriptionStatus::OPEN->value,
        ]);

        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5 class="tracking-count description-header">0</h5>');
        $this->assertResponseContains('<h5 class="tracking-first-date description-header"><code>N/A</code></h5>');
        $this->assertResponseContains('<h5 class="tracking-last-date description-header"><code>N/A</code></h5>');
        $this->assertResponseContains('<h5 class="total-hours description-header">0</h5>');
        $this->assertResponseContains('Ver actividades');
        $this->assertResponseNotContains('Cerrar actividades');

        $first_date = FrozenDate::now()->subDays(4);
        $record[0] = $this->addRecord('StudentTracking', [
            'student_adscription_id' => $adscription->id,
            'date' => $first_date,
            'hours' => 4,
            'description' => 'test',
        ]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5 class="tracking-count description-header">' . 1 . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-first-date description-header">' . $first_date . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-last-date description-header">' . $first_date . '</h5>');
        $this->assertResponseContains('<h5 class="total-hours description-header">' . 4 . '</h5>');
        $this->assertResponseContains('Ver actividades');
        $this->assertResponseNotContains('Cerrar actividades');

        $last_date = FrozenDate::now()->subDays(3);
        $record[1] = $this->addRecord('StudentTracking', [
            'student_adscription_id' => $adscription->id,
            'date' => $last_date,
            'hours' => 4,
            'description' => 'test',
        ]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5 class="tracking-count description-header">' . 2 . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-first-date description-header">' . $first_date . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-last-date description-header">' . $last_date . '</h5>');
        $this->assertResponseContains('<h5 class="total-hours description-header">' . 8 . '</h5>');
        $this->assertResponseContains('Ver actividades');
        $this->assertResponseNotContains('Cerrar actividades');

        $last_date = FrozenDate::now()->subDays(1);
        $record[2] = $this->addRecord('StudentTracking', [
            'student_adscription_id' => $adscription->id,
            'date' => $last_date,
            'hours' => 4,
            'description' => 'test',
        ]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5 class="tracking-count description-header">' . 3 . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-first-date description-header">' . $first_date . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-last-date description-header">' . $last_date . '</h5>');
        $this->assertResponseContains('<h5 class="total-hours description-header">' . 12 . '</h5>');
        $this->assertResponseContains('Ver actividades');
        $this->assertResponseNotContains('Cerrar actividades');

        $this->deleteRecord($record[1]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5 class="tracking-count description-header">' . 2 . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-first-date description-header">' . $first_date . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-last-date description-header">' . $last_date . '</h5>');
        $this->assertResponseContains('<h5 class="total-hours description-header">' . 8 . '</h5>');
        $this->assertResponseContains('Ver actividades');
        $this->assertResponseNotContains('Cerrar actividades');

        $first_date = $last_date;
        $this->deleteRecord($record[0]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5 class="tracking-count description-header">' . 1 . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-first-date description-header">' . $first_date . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-last-date description-header">' . $last_date . '</h5>');
        $this->assertResponseContains('<h5 class="total-hours description-header">' . 4 . '</h5>');
        $this->assertResponseContains('Ver actividades');
        $this->assertResponseNotContains('Cerrar actividades');

        $last_date = FrozenDate::now();
        $record[3] = $this->addRecord('StudentTracking', [
            'student_adscription_id' => $adscription->id,
            'date' => $last_date,
            'hours' => 120,
            'description' => 'test',
        ]);
        $this->get('/student/stages');
        $this->assertResponseOk();
        $this->assertResponseContains('<h5 class="tracking-count description-header">' . 2 . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-first-date description-header">' . $first_date . '</h5>');
        $this->assertResponseContains('<h5 class="tracking-last-date description-header">' . $last_date . '</h5>');
        $this->assertResponseContains('<h5 class="total-hours description-header">' . 124 . '</h5>');
        $this->assertResponseContains('Ver actividades');
        $this->assertResponseContains('Cerrar actividades');
    }
}
