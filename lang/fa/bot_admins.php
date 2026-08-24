<?php

return [
    'main' => [
        'text' => [
            'information' => '⚙️ <b><i>مدیران</i></b>'
                ."\r\n"
                ."\r\n❔ <b>مالک ربات:</b> <i>:botOwner</i>"
                ."\r\n❔ <b>تعداد ادمین‌ها:</b> <i>:adminCount</i>"
                ."\r\n"
                ."\r\nمی‌توانید مدیران ربات را از طریق گزینه‌های زیر مدیریت کنید 👇",
            'enterNewAdminId' => '❓ نام کاربری تلگرام یا آیدی عددی مدیر جدید را وارد کنید:',
            'adminAddedSuccessfully' => '✅ مدیر با موفقیت اضافه شد.',
        ],
        'answers' => [
            'addingNewAdmin' => 'در حال افزودن مدیر جدید...',
            'ownerInfo' => ':ownerName از تاریخ :fromDate مالک این ربات است.',
            'adminRemoved' => 'مدیر ":adminName" با موفقیت حذف شد.',
        ],
        'keys' => [
            'addNewAdmin' => '➕ افزودن مدیر جدید',
            'removeAdmin' => ':adminName 🗑',
            'owner' => 'مالک - :ownerName 👑',
        ],
        'lock-keys' => [
            'addingNewAdmin' => 'در حال افزودن مدیر جدید',
        ],
    ],
    'reply_key' => 'مدیران ربات 🧑‍💻',
];
