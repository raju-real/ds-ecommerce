@include('errors.minimal-page', [
    'code' => 413,
    'title' => 'Upload too large',
    'message' => 'The files you sent are bigger than the server accepts. Go back and choose smaller images, or fewer images at a time.',
    'link' => url()->previous(),
    'linkText' => 'Go back',
])
