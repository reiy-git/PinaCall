<?php

return [
    'portal_url' => env('PORTAL_URL', 'http://localhost:5173'),
    'document_guidance' => env('ENROLLMENT_DOCUMENT_GUIDANCE', 'Upload the supporting documents requested by the Registrar. For example, an academic record may be requested; confirm the actual requirements with the Registrar.'),
    'programs' => ['BS Information Technology', 'BS Computer Science', 'BS Information Systems'],
    'academic_year' => env('ENROLLMENT_ACADEMIC_YEAR', '2026-2027'),
    'semester' => env('ENROLLMENT_SEMESTER', '1st Semester'),
];
