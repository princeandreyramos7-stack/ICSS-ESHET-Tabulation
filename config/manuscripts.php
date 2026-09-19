<?php

/*
|--------------------------------------------------------------------------
| Manuscript uploads
|--------------------------------------------------------------------------
| The largest PDF the application accepts, in megabytes. The effective limit
| is the smaller of this value and the host's upload_max_filesize /
| post_max_size (see App\Support\UploadLimit); the papers form shows the
| effective number.
*/

return [
    'max_upload_mb' => (int) env('MANUSCRIPT_MAX_MB', 25),
];
