<?php

namespace Database\Seeders;

use App\Actions\Inquiries\CreateInquiry;
use App\Models\Inquiry;
use App\Models\InquiryAttachment;
use App\Models\InquiryCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Inquiry::query()->exists()) {
            $this->categories();
            $this->syncExistingDemoStatuses();
            $this->syncDemoAttachments();

            return;
        }

        $categories = $this->categories();
        $creators = $this->creators();
        $createInquiry = app(CreateInquiry::class);

        foreach ($this->records() as $index => $record) {
            $inquiry = $createInquiry->handle([
                'category' => $categories[$record['category']],
                'creator' => $record['anonymous'] ? null : $creators[$index % $creators->count()],
                'title' => $record['title'],
                'description' => $record['description'],
                'anonymous' => $record['anonymous'],
                'submitted_at' => Carbon::parse($record['submitted_at']),
                'number_prefix' => 'KAZM',
            ]);

            $inquiry->forceFill([
                'status' => $record['status'],
                'archived_at' => $record['archived'] ? Carbon::parse($record['submitted_at'])->addDays(3) : null,
            ])->save();
        }

        $this->syncDemoAttachments();
    }

    private function syncExistingDemoStatuses(): void
    {
        foreach ($this->demoStatusOverrides() as $title => $override) {
            Inquiry::query()
                ->where('title', $title)
                ->update([
                    'status' => $override['status'],
                    'archived_at' => $override['archived_at'],
                ]);
        }
    }

    private function syncDemoAttachments(): void
    {
        foreach ($this->demoAttachments() as $title => $attachments) {
            $inquiry = Inquiry::query()
                ->where('title', $title)
                ->first();

            if ($inquiry === null) {
                continue;
            }

            foreach ($attachments as $attachment) {
                $storedName = Str::uuid()->toString().'.'.$attachment['extension'];

                InquiryAttachment::query()->firstOrCreate(
                    [
                        'inquiry_id' => $inquiry->id,
                        'original_name' => $attachment['original_name'],
                    ],
                    [
                        'uploaded_by_id' => $inquiry->created_by_id,
                        'disk' => 'local',
                        'path' => "inquiries/{$inquiry->number}/{$storedName}",
                        'stored_name' => $storedName,
                        'mime_type' => $attachment['mime_type'],
                        'extension' => $attachment['extension'],
                        'file_type' => $attachment['file_type'],
                        'size_bytes' => $attachment['size_bytes'],
                        'checksum' => hash('sha256', $inquiry->number.$attachment['original_name']),
                        'metadata' => ['source' => 'applicant'],
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, array<int, array{
     *     original_name: string,
     *     mime_type: string,
     *     extension: string,
     *     file_type: string,
     *     size_bytes: int
     * }>>
     */
    private function demoAttachments(): array
    {
        return [
            'Неисправен кондиционер в комнате отдыха' => [
                [
                    'original_name' => 'IMG_4955.jpeg',
                    'mime_type' => 'image/jpeg',
                    'extension' => 'jpeg',
                    'file_type' => InquiryAttachment::TYPE_PHOTO,
                    'size_bytes' => 98_304,
                ],
            ],
            'Подозрение на завышение стоимости расходных материалов' => [
                [
                    'original_name' => 'supplier-prices.xlsx',
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'extension' => 'xlsx',
                    'file_type' => InquiryAttachment::TYPE_SPREADSHEET,
                    'size_bytes' => 186_400,
                ],
                [
                    'original_name' => 'comparison-notes.pdf',
                    'mime_type' => 'application/pdf',
                    'extension' => 'pdf',
                    'file_type' => InquiryAttachment::TYPE_PDF,
                    'size_bytes' => 248_200,
                ],
            ],
            'Safety guard missing on workshop equipment' => [
                [
                    'original_name' => 'equipment-photo.jpg',
                    'mime_type' => 'image/jpeg',
                    'extension' => 'jpg',
                    'file_type' => InquiryAttachment::TYPE_PHOTO,
                    'size_bytes' => 132_096,
                ],
                [
                    'original_name' => 'shift-audio-note.mp3',
                    'mime_type' => 'audio/mpeg',
                    'extension' => 'mp3',
                    'file_type' => InquiryAttachment::TYPE_AUDIO,
                    'size_bytes' => 2_412_000,
                ],
            ],
            'Request to review travel expense reimbursement' => [
                [
                    'original_name' => 'receipts.pdf',
                    'mime_type' => 'application/pdf',
                    'extension' => 'pdf',
                    'file_type' => InquiryAttachment::TYPE_PDF,
                    'size_bytes' => 654_200,
                ],
                [
                    'original_name' => 'explanation.docx',
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'extension' => 'docx',
                    'file_type' => InquiryAttachment::TYPE_DOCUMENT,
                    'size_bytes' => 74_800,
                ],
            ],
            'Ошибка в контактных данных подразделения' => [
                [
                    'original_name' => 'old-phone-number.txt',
                    'mime_type' => 'text/plain',
                    'extension' => 'txt',
                    'file_type' => InquiryAttachment::TYPE_TEXT,
                    'size_bytes' => 3_200,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{status: string, archived_at: Carbon|null}>
     */
    private function demoStatusOverrides(): array
    {
        return [
            'Продолжительное телефонное общение на рабочем месте' => [
                'status' => Inquiry::STATUS_SUSPENDED,
                'archived_at' => null,
            ],
            'Concern about pressure to skip quality checks' => [
                'status' => Inquiry::STATUS_REJECTED,
                'archived_at' => null,
            ],
            'Не закрывается аварийный выход на складе' => [
                'status' => Inquiry::STATUS_SUSPENDED,
                'archived_at' => null,
            ],
            'Unclear write-off of warehouse inventory' => [
                'status' => Inquiry::STATUS_WITHDRAWN,
                'archived_at' => null,
            ],
            'Ошибка в контактных данных подразделения' => [
                'status' => Inquiry::STATUS_REJECTED,
                'archived_at' => null,
            ],
            'Request to review travel expense reimbursement' => [
                'status' => Inquiry::STATUS_COMPLETED,
                'archived_at' => Carbon::parse('2026-07-06 18:20:00'),
            ],
            'Просьба проверить корректность начисления премии' => [
                'status' => Inquiry::STATUS_COMPLETED,
                'archived_at' => Carbon::parse('2026-07-05 16:12:00'),
            ],
            'Missing first-aid supplies in the reception area' => [
                'status' => Inquiry::STATUS_COMPLETED,
                'archived_at' => Carbon::parse('2026-07-02 14:22:00'),
            ],
            'Отсутствие обратной связи по заявлению на отпуск' => [
                'status' => Inquiry::STATUS_COMPLETED,
                'archived_at' => Carbon::parse('2026-07-01 13:28:00'),
            ],
        ];
    }

    /**
     * @return array<string, InquiryCategory>
     */
    private function categories(): array
    {
        return [
            'corruption' => $this->category('corruption', 'Corruption reports', 'Reports about possible corruption, conflicts of interest, improper influence, or abuse of authority.', 90, 10),
            'fraud' => $this->category('fraud', 'Fraud and financial violations', 'Suspicions of fraud, theft, misstatement, or improper use of funds.', 60, 20),
            'labor' => $this->category('labor', 'Labor discipline violations', 'Inquiries about schedules, overtime, discipline, working conditions, and internal procedures.', 30, 30),
            'ethics' => $this->category('ethics', 'Ethics and conduct violations', 'Complaints about unethical behavior, pressure, rude treatment, discrimination, or team conflict.', 30, 40),
            'safety' => $this->category('safety', 'Safety and occupational health', 'Health and safety risks, faulty equipment, missing briefings, or missing protective equipment.', 15, 50),
            'other' => $this->category('other', 'Other inquiries', 'Inquiries that do not belong to the main categories and require initial assessment by a responsible employee.', 20, 60),
        ];
    }

    private function category(string $code, string $name, string $description, int $reviewDays, int $sortOrder): InquiryCategory
    {
        $category = InquiryCategory::query()->firstOrCreate(
            ['name_key' => "inquiry_categories.{$code}.name"],
            [
                'fallback_name' => $name,
                'fallback_description' => $description,
                'review_days' => $reviewDays,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ],
        );

        $category->forceFill([
            'name_key' => "inquiry_categories.{$code}.name",
            'description_key' => "inquiry_categories.{$code}.description",
            'fallback_name' => $name,
            'fallback_description' => $description,
            'review_days' => $reviewDays,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ])->save();

        return $category;
    }

    /**
     * @return Collection<int, User>
     */
    private function creators(): Collection
    {
        $creators = User::query()
            ->where('type', 'regular')
            ->orderBy('id')
            ->limit(12)
            ->get();

        if ($creators->isNotEmpty()) {
            return $creators;
        }

        return User::factory()
            ->count(12)
            ->create([
                'type' => 'regular',
                'status' => 'active',
            ]);
    }

    /**
     * @return array<int, array{
     *     category: string,
     *     title: string,
     *     description: string,
     *     status: string,
     *     anonymous: bool,
     *     archived: bool,
     *     submitted_at: string
     * }>
     */
    private function records(): array
    {
        return [
            [
                'category' => 'safety',
                'title' => 'Неисправен кондиционер в комнате отдыха',
                'description' => 'В комнате отдыха на втором этаже кондиционер несколько дней работает с сильным шумом и периодически отключается. Из-за жары сотрудникам сложно находиться в помещении во время перерыва.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-10 19:13:00',
            ],
            [
                'category' => 'other',
                'title' => 'Продолжительное телефонное общение на рабочем месте',
                'description' => 'Сотрудник соседнего отдела регулярно ведет личные разговоры во время приема посетителей. Это мешает работе и создает напряжение среди коллег.',
                'status' => Inquiry::STATUS_SUSPENDED,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-10 18:43:00',
            ],
            [
                'category' => 'fraud',
                'title' => 'Подозрение на завышение стоимости расходных материалов',
                'description' => 'В последних заявках на закуп расходных материалов цена выше обычной почти на треть. Прошу проверить коммерческие предложения и порядок выбора поставщика.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-10 12:39:00',
            ],
            [
                'category' => 'labor',
                'title' => 'Задержка выдачи спецодежды новым сотрудникам',
                'description' => 'Несколько новых сотрудников вышли на смену без полного комплекта спецодежды. Руководитель участка сообщил, что поставка задерживается, но сроки не названы.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-09 17:22:00',
            ],
            [
                'category' => 'corruption',
                'title' => 'Подозрение на конфликт интересов при закупке',
                'description' => 'Один из участников комиссии ранее работал у поставщика, который выиграл закупку. Прошу проверить, был ли заявлен конфликт интересов и соблюдена ли процедура.',
                'status' => Inquiry::STATUS_IN_PROGRESS,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-09 15:48:00',
            ],
            [
                'category' => 'ethics',
                'title' => 'Некорректное общение руководителя на планерке',
                'description' => 'Во время утренней планерки руководитель публично повысил голос на сотрудника и сделал личные замечания. Прошу провести беседу и напомнить о стандартах общения.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-09 11:05:00',
            ],
            [
                'category' => 'safety',
                'title' => 'Жұмыс орнында қауіпсіздік нұсқаулығы өткізілмеді',
                'description' => 'Жаңа жабдық іске қосылғаннан кейін ауысым қызметкерлеріне қауіпсіздік бойынша толық нұсқаулық берілген жоқ. Операторлар құрылғыны тәжірибе арқылы үйреніп жатыр.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-08 16:40:00',
            ],
            [
                'category' => 'ethics',
                'title' => 'Басшы тарапынан дөрекі қарым-қатынас байқалды',
                'description' => 'Бөлім басшысы қызметкерлермен сөйлескенде жиі дөрекі сөздер қолданады. Бұл ұжымдағы атмосфераға және жұмыс сапасына кері әсер етеді.',
                'status' => Inquiry::STATUS_IN_PROGRESS,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-08 09:18:00',
            ],
            [
                'category' => 'fraud',
                'title' => 'Сатып алу кезінде таныстық арқылы шешім қабылданды деген күдік',
                'description' => 'Бір жеткізуші бірнеше рет конкурссыз таңдалды. Қызметкерлер арасында оның жауапты маманмен жеке байланысы бар деген ақпарат айтылып жүр.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-07 14:31:00',
            ],
            [
                'category' => 'other',
                'title' => 'Асханада санитарлық талаптар сақталмайды',
                'description' => 'Түскі уақытта үстелдер уақытында тазаланбайды, ал ыстық тағамдар кейде салқындап беріледі. Асханадағы бақылауды күшейтуді сұраймын.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-07 12:10:00',
            ],
            [
                'category' => 'labor',
                'title' => 'Ауысым кестесі алдын ала хабарланбай өзгереді',
                'description' => 'Соңғы екі аптада ауысым кестесі бір күн бұрын бірнеше рет өзгерді. Жеке жоспар құру мүмкін болмай қалды.',
                'status' => Inquiry::STATUS_IN_PROGRESS,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-06 18:55:00',
            ],
            [
                'category' => 'corruption',
                'title' => 'Мердігерден сыйлық сұрау туралы ақпарат',
                'description' => 'Мердігер компания өкілі бейресми сыйлық сұралғанын айтты. Нақты деректерді жауапты қызметкермен жеке бөлісуге дайынмын.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-06 10:26:00',
            ],
            [
                'category' => 'fraud',
                'title' => 'Possible misuse of company fuel cards',
                'description' => 'Fuel card transactions show weekend purchases that do not match approved business trips. Please review the July statements and vehicle assignment records.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-05 20:15:00',
            ],
            [
                'category' => 'labor',
                'title' => 'Repeated overtime without written approval',
                'description' => 'Our team has worked late three times this month, but the overtime was not documented in advance. I would like the process clarified and the hours reviewed.',
                'status' => Inquiry::STATUS_IN_PROGRESS,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-05 17:04:00',
            ],
            [
                'category' => 'corruption',
                'title' => 'Anonymous concern about vendor selection',
                'description' => 'A vendor was selected even though two lower bids were available. The justification was not shared with the team, and the decision appears inconsistent with the procurement rules.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-04 13:44:00',
            ],
            [
                'category' => 'safety',
                'title' => 'Safety guard missing on workshop equipment',
                'description' => 'The protective guard on the cutting table has been removed during maintenance and was not installed back. Operators continue using the equipment.',
                'status' => Inquiry::STATUS_IN_PROGRESS,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-04 08:37:00',
            ],
            [
                'category' => 'other',
                'title' => 'Request to review travel expense reimbursement',
                'description' => 'My reimbursement request for a June business trip was returned twice without a clear reason. Please check whether the uploaded receipts meet the policy requirements.',
                'status' => Inquiry::STATUS_COMPLETED,
                'anonymous' => false,
                'archived' => true,
                'submitted_at' => '2026-07-03 18:20:00',
            ],
            [
                'category' => 'ethics',
                'title' => 'Concern about pressure to skip quality checks',
                'description' => 'During a busy shift, employees were told to finish the batch without completing the standard checklist. I am concerned this may become a repeated practice.',
                'status' => Inquiry::STATUS_REJECTED,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-03 12:07:00',
            ],
            [
                'category' => 'labor',
                'title' => 'Просьба проверить корректность начисления премии',
                'description' => 'В расчетном листе премия за июнь ниже ожидаемой, хотя показатели отдела выполнены. Прошу проверить расчет и объяснить примененные коэффициенты.',
                'status' => Inquiry::STATUS_COMPLETED,
                'anonymous' => false,
                'archived' => true,
                'submitted_at' => '2026-07-02 16:12:00',
            ],
            [
                'category' => 'safety',
                'title' => 'Не закрывается аварийный выход на складе',
                'description' => 'Дверь аварийного выхода на складе закрывается неплотно после замены замка. Вечером она может оставаться приоткрытой.',
                'status' => Inquiry::STATUS_SUSPENDED,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-02 09:52:00',
            ],
            [
                'category' => 'fraud',
                'title' => 'Расхождение между актом выполненных работ и фактическим объемом',
                'description' => 'По договору указано выполнение полного объема работ, но часть задач на объекте еще не завершена. Прошу сверить акт с фактическим состоянием.',
                'status' => Inquiry::STATUS_IN_PROGRESS,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-07-01 15:35:00',
            ],
            [
                'category' => 'ethics',
                'title' => 'Қызметкерді көпшілік алдында негізсіз сынау',
                'description' => 'Жиналыс кезінде қызметкердің жұмысы нақты дәлелсіз қатты сыналды. Мұндай жағдай бірнеше рет қайталанды.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-07-01 11:19:00',
            ],
            [
                'category' => 'other',
                'title' => 'Құжат айналымы бойынша өтініштің ұзақ қаралуы',
                'description' => 'Ішкі жүйеде келісуге жіберілген құжат бес жұмыс күнінен бері қозғалыссыз тұр. Жауапты бөлімнен мәртебесін нақтылауды сұраймын.',
                'status' => Inquiry::STATUS_IN_PROGRESS,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-06-30 17:41:00',
            ],
            [
                'category' => 'corruption',
                'title' => 'Жобаға байланысты шешім қабылдауда ықпал ету белгісі',
                'description' => 'Жоба бойынша ұсынылған техникалық шешім дәлелсіз өзгертілді. Өзгеріс белгілі бір мердігердің мүддесіне сай келуі мүмкін.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-06-30 10:08:00',
            ],
            [
                'category' => 'safety',
                'title' => 'Missing first-aid supplies in the reception area',
                'description' => 'The first-aid kit near reception is missing basic supplies after the last incident. Please restock it and assign someone to check it regularly.',
                'status' => Inquiry::STATUS_COMPLETED,
                'anonymous' => false,
                'archived' => true,
                'submitted_at' => '2026-06-29 14:22:00',
            ],
            [
                'category' => 'ethics',
                'title' => 'Unequal distribution of weekend shifts',
                'description' => 'The same employees are assigned weekend shifts more often than others without a published rotation. The schedule should be reviewed for fairness.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-06-29 09:45:00',
            ],
            [
                'category' => 'fraud',
                'title' => 'Unclear write-off of warehouse inventory',
                'description' => 'Several inventory items were written off last week without supporting photos or a technical report. Please verify the approval documents.',
                'status' => Inquiry::STATUS_WITHDRAWN,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-06-28 19:06:00',
            ],
            [
                'category' => 'labor',
                'title' => 'Отсутствие обратной связи по заявлению на отпуск',
                'description' => 'Заявление на отпуск подано через систему две недели назад, но статус не изменился. Прошу сообщить, кто должен согласовать заявку.',
                'status' => Inquiry::STATUS_COMPLETED,
                'anonymous' => false,
                'archived' => true,
                'submitted_at' => '2026-06-28 13:28:00',
            ],
            [
                'category' => 'other',
                'title' => 'Ошибка в контактных данных подразделения',
                'description' => 'На внутренней странице подразделения указан старый номер телефона. Из-за этого обращения клиентов попадают не тому сотруднику.',
                'status' => Inquiry::STATUS_REJECTED,
                'anonymous' => false,
                'archived' => false,
                'submitted_at' => '2026-06-27 16:53:00',
            ],
            [
                'category' => 'corruption',
                'title' => 'Request to check approval of a single-source contract',
                'description' => 'A single-source contract was approved for urgent services, but the same services were planned earlier in the quarter. Please review whether the urgency justification is valid.',
                'status' => Inquiry::STATUS_NEW,
                'anonymous' => true,
                'archived' => false,
                'submitted_at' => '2026-06-27 10:11:00',
            ],
        ];
    }
}
