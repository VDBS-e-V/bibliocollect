<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Actions\BlockPatronAction;
use App\Modules\Patrons\Actions\DepartPatronAction;
use App\Modules\Patrons\Actions\UnblockPatronAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronAccountLinkToken;
use App\Modules\Patrons\Models\PatronBlockEvent;
use App\Modules\Patrons\Support\PatronLinkCodeHasher;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DemoSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Bibliothek2026!';

    public const DEMO_LINK_CODE = 'D3MZ2-26ABC';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder darf nicht in production ausgeführt werden.');
        }

        $classes = $this->seedSchool();
        $patrons = $this->seedPatrons($classes);
        $users = $this->seedUsers($patrons);

        $this->seedPatronWorkflowStates($patrons, $users['staff']);
        $this->seedPatronLinkTokens($patrons, $users['staff']);
        $this->seedCatalog();

        $this->command?->newLine();
        $this->command?->info('BiblioCollect-Demo-Daten wurden angelegt.');
        $this->command?->line('Demo-Passwort: '.self::DEMO_PASSWORD);
        $this->command?->line('Offener Patron-Linkcode für Noah Linkcode: '.self::DEMO_LINK_CODE);
        $this->command?->line('Weitere Konten und Beispieldaten: docs/DEVELOPMENT_SEED.md');
    }

    /**
     * @return array<string, SchoolClass>
     */
    private function seedSchool(): array
    {
        $years = [
            'previous' => SchoolYear::query()->updateOrCreate(
                ['name' => '2025/26'],
                [
                    'starts_on' => '2025-08-01',
                    'ends_on' => '2026-07-31',
                    'is_active' => false,
                ],
            ),
            'current' => SchoolYear::query()->updateOrCreate(
                ['name' => '2026/27'],
                [
                    'starts_on' => '2026-08-01',
                    'ends_on' => '2027-07-31',
                    'is_active' => true,
                ],
            ),
            'next' => SchoolYear::query()->updateOrCreate(
                ['name' => '2027/28'],
                [
                    'starts_on' => '2027-08-01',
                    'ends_on' => '2028-07-31',
                    'is_active' => false,
                ],
            ),
        ];

        $classes = [];
        $classPlans = [
            'previous' => [
                ['4a', 4], ['5a', 5], ['6a', 6], ['7a', 7], ['8a', 8], ['9a', 9],
            ],
            'current' => [
                ['5a', 5], ['6a', 6], ['7a', 7], ['8a', 8], ['9a', 9], ['10a', 10],
            ],
            'next' => [
                ['6a', 6], ['7a', 7], ['8a', 8], ['9a', 9], ['10a', 10], ['11a', 11],
            ],
        ];

        foreach ($classPlans as $period => $plan) {
            foreach ($plan as [$name, $gradeLevel]) {
                $classes[$period.':'.$name] = SchoolClass::query()->updateOrCreate(
                    [
                        'school_year_id' => $years[$period]->getKey(),
                        'name' => $name,
                    ],
                    [
                        'grade_level' => $gradeLevel,
                        'is_active' => true,
                    ],
                );
            }
        }

        $openingHours = [
            1 => [true, '09:00', '15:00'],
            2 => [true, '09:00', '15:00'],
            3 => [true, '09:00', '13:00'],
            4 => [true, '09:00', '15:00'],
            5 => [true, '09:00', '13:00'],
            6 => [false, null, null],
            7 => [false, null, null],
        ];

        foreach ($openingHours as $dayOfWeek => [$isOpen, $opensAt, $closesAt]) {
            LibraryOpeningHour::query()->updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                [
                    'is_open' => $isOpen,
                    'opens_at' => $opensAt,
                    'closes_at' => $closesAt,
                ],
            );
        }

        foreach ([
            '2026-10-19' => 'Herbstferien',
            '2026-10-20' => 'Herbstferien',
            '2026-12-24' => 'Weihnachtsferien',
            '2026-12-31' => 'Jahreswechsel',
        ] as $date => $reason) {
            $closure = LibraryClosure::query()
                ->whereDate('date', $date)
                ->first();

            $closure ??= new LibraryClosure;

            $closure->forceFill([
                'date' => $date,
                'reason' => $reason,
            ])->save();
        }

        return $classes;
    }

    /**
     * @param  array<string, SchoolClass>  $classes
     * @return array<string, Patron>
     */
    private function seedPatrons(array $classes): array
    {
        return [
            'student' => $this->upsertPatron(
                libraryNumber: 'S-10001',
                kind: PatronKind::Student,
                initialStatus: PatronStatus::Active,
                firstName: 'Lina',
                lastName: 'Berger',
                birthDate: '2013-04-15',
                email: 'lina.berger@demo.bibliocollect.test',
                schoolClass: $classes['current:7a'],
            ),
            'ag_basic' => $this->upsertPatron(
                libraryNumber: 'S-10002',
                kind: PatronKind::Student,
                initialStatus: PatronStatus::Active,
                firstName: 'Mika',
                lastName: 'Beispiel',
                birthDate: '2012-05-10',
                email: null,
                schoolClass: $classes['current:8a'],
            ),
            'ag_extended' => $this->upsertPatron(
                libraryNumber: 'S-10003',
                kind: PatronKind::Student,
                initialStatus: PatronStatus::Active,
                firstName: 'Samira',
                lastName: 'Kaya',
                birthDate: '2011-11-03',
                email: 'samira.kaya@demo.bibliocollect.test',
                schoolClass: $classes['current:9a'],
            ),
            'link_code' => $this->upsertPatron(
                libraryNumber: 'S-10004',
                kind: PatronKind::Student,
                initialStatus: PatronStatus::Active,
                firstName: 'Noah',
                lastName: 'Linkcode',
                birthDate: '2014-02-21',
                email: null,
                schoolClass: $classes['current:6a'],
            ),
            'block_history' => $this->upsertPatron(
                libraryNumber: 'S-10005',
                kind: PatronKind::Student,
                initialStatus: PatronStatus::Active,
                firstName: 'Emil',
                lastName: 'Hartmann',
                birthDate: '2010-08-19',
                email: null,
                schoolClass: $classes['current:10a'],
            ),
            'departed' => $this->upsertPatron(
                libraryNumber: 'S-10006',
                kind: PatronKind::Student,
                initialStatus: PatronStatus::Active,
                firstName: 'Paula',
                lastName: 'Alt',
                birthDate: '2010-01-12',
                email: 'paula.alt@demo.bibliocollect.test',
                schoolClass: $classes['current:10a'],
            ),
            'teacher' => $this->upsertPatron(
                libraryNumber: 'L-20001',
                kind: PatronKind::Teacher,
                initialStatus: PatronStatus::Active,
                firstName: 'Anna',
                lastName: 'Lehrkraft',
                birthDate: '1984-02-11',
                email: 'anna.lehrkraft@demo.bibliocollect.test',
                schoolClass: null,
            ),
            'employee' => $this->upsertPatron(
                libraryNumber: 'M-30001',
                kind: PatronKind::Employee,
                initialStatus: PatronStatus::Active,
                firstName: 'Alex',
                lastName: 'Bibliothek',
                birthDate: '1991-09-08',
                email: 'alex.bibliothek@demo.bibliocollect.test',
                schoolClass: null,
            ),
            'archived' => $this->upsertPatron(
                libraryNumber: 'M-30002',
                kind: PatronKind::Employee,
                initialStatus: PatronStatus::Archived,
                firstName: 'Mara',
                lastName: 'Archiv',
                birthDate: '1978-06-30',
                email: null,
                schoolClass: null,
            ),
        ];
    }

    private function upsertPatron(
        string $libraryNumber,
        PatronKind $kind,
        PatronStatus $initialStatus,
        string $firstName,
        string $lastName,
        string $birthDate,
        ?string $email,
        ?SchoolClass $schoolClass,
    ): Patron {
        $patron = Patron::query()->firstOrNew(['library_number' => $libraryNumber]);

        if (! $patron->exists) {
            $patron->forceFill([
                'kind' => $kind,
                'status' => $initialStatus,
            ]);
        }

        $attributes = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $birthDate,
            'email' => $email,
        ];

        if ($patron->status !== PatronStatus::Departed) {
            $attributes['school_class_id'] = $schoolClass?->getKey();
        }

        $patron->forceFill($attributes)->save();

        return $patron;
    }

    /**
     * @param  array<string, Patron>  $patrons
     * @return array<string, User>
     */
    private function seedUsers(array $patrons): array
    {
        $management = $this->upsertDemoUser(
            name: 'Demo Verwaltung',
            email: 'management@demo.bibliocollect.test',
            roleKeys: ['management'],
            patron: null,
            assignedBy: null,
        );

        return [
            'management' => $management,
            'staff' => $this->upsertDemoUser(
                name: 'Demo Mitarbeiterin',
                email: 'staff@demo.bibliocollect.test',
                roleKeys: ['staff'],
                patron: null,
                assignedBy: $management,
            ),
            'technical_admin' => $this->upsertDemoUser(
                name: 'Demo Technik',
                email: 'technik@demo.bibliocollect.test',
                roleKeys: ['technical_admin'],
                patron: null,
                assignedBy: $management,
            ),
            'teacher' => $this->upsertDemoUser(
                name: 'Anna Lehrkraft',
                email: 'teacher@demo.bibliocollect.test',
                roleKeys: ['teacher'],
                patron: $patrons['teacher'],
                assignedBy: $management,
            ),
            'student' => $this->upsertDemoUser(
                name: 'Lina Berger',
                email: 'student@demo.bibliocollect.test',
                roleKeys: ['student'],
                patron: $patrons['student'],
                assignedBy: $management,
            ),
            'ag_basic' => $this->upsertDemoUser(
                name: 'Mika Beispiel',
                email: 'ag-basic@demo.bibliocollect.test',
                roleKeys: ['student', 'student_ag_basic'],
                patron: $patrons['ag_basic'],
                assignedBy: $management,
            ),
            'ag_extended' => $this->upsertDemoUser(
                name: 'Samira Kaya',
                email: 'ag-extended@demo.bibliocollect.test',
                roleKeys: ['student', 'student_ag_extended'],
                patron: $patrons['ag_extended'],
                assignedBy: $management,
            ),
            'departed' => $this->upsertDemoUser(
                name: 'Paula Alt',
                email: 'departed@demo.bibliocollect.test',
                roleKeys: ['student'],
                patron: $patrons['departed'],
                assignedBy: $management,
                resetDisabledState: false,
            ),
        ];
    }

    /**
     * @param  list<string>  $roleKeys
     */
    private function upsertDemoUser(
        string $name,
        string $email,
        array $roleKeys,
        ?Patron $patron,
        ?User $assignedBy,
        bool $resetDisabledState = true,
    ): User {
        $user = User::query()->firstOrNew(['email' => $email]);

        $attributes = [
            'name' => $name,
            'password' => self::DEMO_PASSWORD,
            'email_verified_at' => now(),
            'patron_id' => $patron?->getKey(),
        ];

        if ($resetDisabledState) {
            $attributes['disabled_at'] = null;
            $attributes['disabled_reason'] = null;
        }

        $user->forceFill($attributes)->save();
        $user->roleAssignments()->delete();

        $assignRole = app(AssignRoleAction::class);

        foreach ($roleKeys as $roleKey) {
            $assignRole->execute($user, $roleKey, $assignedBy);
        }

        return $user;
    }

    /**
     * @param  array<string, Patron>  $patrons
     */
    private function seedPatronWorkflowStates(array $patrons, User $staff): void
    {
        $blockedPatron = $patrons['ag_extended'];

        if ($blockedPatron->status === PatronStatus::Active && $blockedPatron->blocked_at === null) {
            app(BlockPatronAction::class)->execute(
                $blockedPatron,
                'Demo-Sperre: offener Ersatzfall',
                $staff,
            );
        }

        $historyPatron = $patrons['block_history'];

        if (! PatronBlockEvent::query()->where('patron_id', $historyPatron->getKey())->exists()) {
            app(BlockPatronAction::class)->execute(
                $historyPatron,
                'Demo-Historie: Medium zunächst vermisst',
                $staff,
            );
            app(UnblockPatronAction::class)->execute($historyPatron, $staff);
        }

        $departedPatron = $patrons['departed'];

        if ($departedPatron->status === PatronStatus::Active) {
            app(DepartPatronAction::class)->execute(
                $departedPatron,
                '2026-07-31',
                $staff,
            );
        }
    }

    /**
     * @param  array<string, Patron>  $patrons
     */
    private function seedPatronLinkTokens(array $patrons, User $staff): void
    {
        $hasher = app(PatronLinkCodeHasher::class);

        $this->replaceLinkToken(
            patron: $patrons['link_code'],
            fingerprint: $hasher->fingerprint(self::DEMO_LINK_CODE),
            staff: $staff,
            usedAt: null,
            revokedAt: null,
        );

        $this->replaceLinkToken(
            patron: $patrons['student'],
            fingerprint: $hasher->fingerprint('US3D2-26ABC'),
            staff: $staff,
            usedAt: now()->subDays(3),
            revokedAt: null,
        );

        $this->replaceLinkToken(
            patron: $patrons['departed'],
            fingerprint: $hasher->fingerprint('R3V8K-26ABC'),
            staff: $staff,
            usedAt: null,
            revokedAt: now()->subDay(),
        );
    }

    private function replaceLinkToken(
        Patron $patron,
        string $fingerprint,
        User $staff,
        mixed $usedAt,
        mixed $revokedAt,
    ): void {
        PatronAccountLinkToken::query()
            ->where('patron_id', $patron->getKey())
            ->delete();

        PatronAccountLinkToken::query()->create([
            'patron_id' => $patron->getKey(),
            'fingerprint' => $fingerprint,
            'expires_at' => now()->addDays(30),
            'used_at' => $usedAt,
            'revoked_at' => $revokedAt,
            'issued_by_user_id' => $staff->getKey(),
        ]);
    }

    private function seedCatalog(): void
    {
        $catalog = [
            [
                'title' => ['Momo', 'Ein Märchen-Roman', 'Momo'],
                'contributors' => [
                    ['Michael Ende', 'Ende, Michael', 'author', 1],
                ],
                'editions' => [
                    [
                        'Neuausgabe', '9783522202803', 'Thienemann', 2023, 'book', 'de', 10, 'ab 10',
                        [
                            ['BC-MOMO-001', CopyStatus::Active, 'J 5 ENDE'],
                            ['BC-MOMO-002', CopyStatus::Damaged, 'J 5 ENDE'],
                        ],
                    ],
                    [
                        'Hörbuchausgabe', '9783844912343', 'Der Hörverlag', 2020, 'audiobook', 'de', 10, 'ab 10',
                        [
                            ['BC-MOMO-H01', CopyStatus::Active, 'HÖR ENDE'],
                        ],
                    ],
                ],
            ],
            [
                'title' => ['Die unendliche Geschichte', null, 'Unendliche Geschichte'],
                'contributors' => [
                    ['Michael Ende', 'Ende, Michael', 'author', 1],
                ],
                'editions' => [
                    [
                        'Neuausgabe', '9783522202605', 'Thienemann', 2022, 'book', 'de', 12, 'ab 12',
                        [
                            ['BC-UNEND-001', CopyStatus::Active, 'J 6 ENDE'],
                            ['BC-UNEND-002', CopyStatus::Lost, 'J 6 ENDE'],
                        ],
                    ],
                ],
            ],
            [
                'title' => ['Tschick', null, 'Tschick'],
                'contributors' => [
                    ['Wolfgang Herrndorf', 'Herrndorf, Wolfgang', 'author', 1],
                ],
                'editions' => [
                    [
                        'Taschenbuch', '9783499256356', 'Rowohlt', 2012, 'book', 'de', 14, 'ab 14',
                        [
                            ['BC-TSCH-001', CopyStatus::Active, 'J 8 HERR'],
                            ['BC-TSCH-002', CopyStatus::Active, 'J 8 HERR'],
                        ],
                    ],
                ],
            ],
            [
                'title' => ['Krabat', null, 'Krabat'],
                'contributors' => [
                    ['Otfried Preußler', 'Preußler, Otfried', 'author', 1],
                ],
                'editions' => [
                    [
                        'Schulausgabe', '9783522177669', 'Thienemann', 2021, 'book', 'de', 12, 'ab 12',
                        [
                            ['BC-KRAB-001', CopyStatus::Active, 'J 7 PREU'],
                            ['BC-KRAB-002', CopyStatus::Withdrawn, 'MAG PREU'],
                        ],
                    ],
                ],
            ],
            [
                'title' => ['Der kleine Prinz', null, 'Kleine Prinz'],
                'contributors' => [
                    ['Antoine de Saint-Exupéry', 'Saint-Exupéry, Antoine de', 'author', 1],
                ],
                'editions' => [
                    [
                        'Leseausgabe', '9783150090784', 'Reclam', 2015, 'book', 'de', 8, 'ab 8',
                        [
                            ['BC-PRINZ-001', CopyStatus::Active, 'J 4 SAIN'],
                        ],
                    ],
                ],
            ],
            [
                'title' => ['Matilda', null, 'Matilda'],
                'contributors' => [
                    ['Roald Dahl', 'Dahl, Roald', 'author', 1],
                    ['Quentin Blake', 'Blake, Quentin', 'illustrator', 2],
                ],
                'editions' => [
                    [
                        'Kinderbuchausgabe', '9783499214110', 'Rowohlt', 2020, 'book', 'de', 10, 'ab 10',
                        [
                            ['BC-MATI-001', CopyStatus::Active, 'J 5 DAHL'],
                            ['BC-MATI-002', CopyStatus::Active, 'J 5 DAHL'],
                        ],
                    ],
                ],
            ],
            [
                'title' => ['Harry Potter und der Stein der Weisen', null, 'Harry Potter und der Stein der Weisen'],
                'contributors' => [
                    ['J. K. Rowling', 'Rowling, J. K.', 'author', 1],
                    ['Klaus Fritz', 'Fritz, Klaus', 'translator', 2],
                ],
                'editions' => [
                    [
                        'Jubiläumsausgabe', '9783551551672', 'Carlsen', 2018, 'book', 'de', 10, 'ab 10',
                        [
                            ['BC-HP001-001', CopyStatus::Active, 'J 6 ROWL'],
                            ['BC-HP001-002', CopyStatus::Active, 'J 6 ROWL'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($catalog as $record) {
            [$preferredTitle, $subtitle, $sortTitle] = $record['title'];

            $title = Title::query()->updateOrCreate(
                ['preferred_title' => $preferredTitle],
                [
                    'subtitle' => $subtitle,
                    'sort_title' => $sortTitle,
                ],
            );

            foreach ($record['contributors'] as [$displayName, $sortName, $roleKey, $position]) {
                $contributor = Contributor::query()->firstOrCreate(
                    ['display_name' => $displayName],
                    ['sort_name' => $sortName],
                );

                if ($contributor->sort_name !== $sortName) {
                    $contributor->forceFill(['sort_name' => $sortName])->save();
                }

                TitleContribution::query()->updateOrCreate(
                    [
                        'title_id' => $title->getKey(),
                        'contributor_id' => $contributor->getKey(),
                        'role_key' => $roleKey,
                    ],
                    ['position' => $position],
                );
            }

            foreach ($record['editions'] as $editionRecord) {
                [
                    $editionStatement,
                    $isbn,
                    $publisherName,
                    $publicationYear,
                    $mediaType,
                    $languageCode,
                    $minimumAge,
                    $ageRatingLabel,
                    $copies,
                ] = $editionRecord;

                $edition = Edition::query()->updateOrCreate(
                    [
                        'title_id' => $title->getKey(),
                        'isbn' => $isbn,
                    ],
                    [
                        'edition_statement' => $editionStatement,
                        'publisher_name' => $publisherName,
                        'publication_year' => $publicationYear,
                        'media_type' => $mediaType,
                        'language_code' => $languageCode,
                        'minimum_age' => $minimumAge,
                        'age_rating_label' => $ageRatingLabel,
                    ],
                );

                foreach ($copies as [$barcode, $status, $shelfLocation]) {
                    Copy::query()->updateOrCreate(
                        ['barcode' => $barcode],
                        [
                            'edition_id' => $edition->getKey(),
                            'status' => $status,
                            'shelf_location' => $shelfLocation,
                        ],
                    );
                }
            }
        }
    }
}
