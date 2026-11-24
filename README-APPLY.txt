CadetCore - update notes
========================

HOW TO APPLY
1. Extract this zip INTO your cadetcore folder (replace the files).
2. In that folder run:
       php artisan migrate
       php artisan optimize:clear
3. Refresh the browser (Ctrl+F5). Cadets who are already logged in see the new sidebar name after they sign in again.

WHAT CHANGED
 - Add New Cadet: cadet type (Regular / Aspiring), separate last / first / middle name (middle optional).
 - "Student No." is now "Serial No.".
 - Officers page: + Add New Officer, plus Edit and Remove on every officer row (not the Corps Adjutant).
   Remove clears the position and switches the login off; the person's records are kept.
 - Cadet Records: Edit button on every row (cadet type, name, serial no., unit, course and year level).
 - Sidebar name for cadets no longer starts with the word "Cadet".
 - Courses: the migration expands abbreviations (BSIT, BSIS, BSCS) to the full name. Add more codes in
   config/cadetcore.php under 'course_names' BEFORE running php artisan migrate.
 - Email: the Forgot password dialog now really sends a reset link (valid 60 minutes) to the email saved on the account.
   The email can also be used instead of the serial number on the login screen.
   Cadets give their email at Sign Up. Officers get theirs in Add New Officer / Edit.

SETTING UP EMAIL (.env)
 Right now MAIL_MAILER=log, which does NOT send anything: the email is written to storage/logs/laravel.log
 (open it and copy the link to test). To send real email, for example with a Gmail account (use an App Password,
 not your normal password), set these in .env and run  php artisan config:clear :
       MAIL_MAILER=smtp
       MAIL_HOST=smtp.gmail.com
       MAIL_PORT=587
       MAIL_SCHEME=tls
       MAIL_USERNAME=your.address@gmail.com
       MAIL_PASSWORD=your-16-character-app-password
       MAIL_FROM_ADDRESS=your.address@gmail.com
       MAIL_FROM_NAME="CadetCore"
       APP_URL=http://localhost/dashboard/cadetcore/public    (the address you open the site with; the link in the email uses it)
