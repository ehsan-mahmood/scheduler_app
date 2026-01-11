<?php

// ============================================
// DATABASE MIGRATIONS
// ============================================

// database/migrations/2024_01_01_000001_create_students_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->boolean('otp_verified')->default(false);
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
}

// database/migrations/2024_01_01_000002_create_instructors_table.php
class CreateInstructorsTable extends Migration
{
    public function up()
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->integer('max_hours_per_week')->default(40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('instructors');
    }
}

// database/migrations/2024_01_01_000003_create_lesson_types_table.php
class CreateLessonTypesTable extends Migration
{
    public function up()
    {
        Schema::create('lesson_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_types');
    }
}

// database/migrations/2024_01_01_000004_create_lessons_table.php
class CreateLessonsTable extends Migration
{
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_type_id')->constrained();
            $table->foreignId('instructor_id')->constrained();
            $table->foreignId('student_id')->constrained();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending_deposit', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending_deposit');
            $table->boolean('deposit_paid')->default(false);
            $table->string('lesson_otp')->nullable();
            $table->timestamp('lesson_otp_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lessons');
    }
}

// database/migrations/2024_01_01_000005_create_payment_deposits_table.php
class CreatePaymentDepositsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->string('payid_reference')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_deposits');
    }
}

// database/migrations/2024_01_01_000006_create_sms_notifications_table.php
class CreateSmsNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('sms_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->nullable()->constrained();
            $table->string('phone');
            $table->enum('type', ['booking', 'deposit_confirmed', 'reschedule', 'cancel', 'lesson_otp', 'reminder']);
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_notifications');
    }
}

// ============================================
// MODELS
// ============================================

// app/Models/Student.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['name', 'phone', 'otp_verified', 'otp_code', 'otp_expires_at'];
    
    protected $casts = [
        'otp_verified' => 'boolean',
        'otp_expires_at' => 'datetime',
    ];

    protected $hidden = ['otp_code'];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

// app/Models/Instructor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'max_hours_per_week', 'is_active'];
    
    protected $casts = ['is_active' => 'boolean'];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

// app/Models/LessonType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonType extends Model
{
    protected $fillable = ['name', 'description', 'duration_minutes', 'price', 'is_active'];
    
    protected $casts = ['is_active' => 'boolean', 'price' => 'decimal:2'];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

// app/Models/Lesson.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'lesson_type_id', 'instructor_id', 'student_id', 'scheduled_at',
        'status', 'deposit_paid', 'lesson_otp', 'lesson_otp_expires_at', 'notes'
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'deposit_paid' => 'boolean',
        'lesson_otp_expires_at' => 'datetime',
    ];

    public function lessonType()
    {
        return $this->belongsTo(LessonType::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function paymentDeposit()
    {
        return $this->hasOne(PaymentDeposit::class);
    }

    public function smsNotifications()
    {
        return $this->hasMany(SmsNotification::class);
    }
}

// app/Models/PaymentDeposit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDeposit extends Model
{
    protected $fillable = ['lesson_id', 'amount', 'payid_reference', 'status', 'verified_at'];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}

// app/Models/SmsNotification.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsNotification extends Model
{
    protected $fillable = ['lesson_id', 'phone', 'type', 'message', 'status', 'sent_at', 'error_message'];
    
    protected $casts = ['sent_at' => 'datetime'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}

// ============================================
// SERVICES
// ============================================

// app/Services/OtpService.php
namespace App\Services;

use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpService
{
    public function generateStudentOtp(Student $student): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $student->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        return $otp;
    }

    public function verifyStudentOtp(Student $student, string $otp): bool
    {
        if ($student->otp_code !== $otp) {
            return false;
        }

        if (Carbon::now()->isAfter($student->otp_expires_at)) {
            return false;
        }

        $student->update([
            'otp_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return true;
    }

    public function generateLessonOtp(Lesson $lesson): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $lesson->update([
            'lesson_otp' => $otp,
            'lesson_otp_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        return $otp;
    }

    public function verifyLessonOtp(Lesson $lesson, string $otp): bool
    {
        if ($lesson->lesson_otp !== $otp) {
            return false;
        }

        if (Carbon::now()->isAfter($lesson->lesson_otp_expires_at)) {
            return false;
        }

        $lesson->update([
            'status' => 'in_progress',
            'lesson_otp' => null,
            'lesson_otp_expires_at' => null,
        ]);

        return true;
    }
}

// app/Services/SmsService.php
namespace App\Services;

use App\Models\SmsNotification;
use App\Models\Lesson;

class SmsService
{
    public function sendBookingConfirmation(Lesson $lesson)
    {
        $message = "Lesson booked! Type: {$lesson->lessonType->name}, Date: {$lesson->scheduled_at->format('Y-m-d H:i')}. Please submit deposit to confirm.";
        
        $this->queueSms($lesson->student->phone, $message, 'booking', $lesson->id);
    }

    public function sendDepositConfirmed(Lesson $lesson)
    {
        $studentMsg = "Deposit confirmed! Your lesson on {$lesson->scheduled_at->format('Y-m-d H:i')} is now confirmed.";
        $instructorMsg = "New lesson confirmed with {$lesson->student->name} on {$lesson->scheduled_at->format('Y-m-d H:i')}.";
        
        $this->queueSms($lesson->student->phone, $studentMsg, 'deposit_confirmed', $lesson->id);
        $this->queueSms($lesson->instructor->phone, $instructorMsg, 'deposit_confirmed', $lesson->id);
    }

    public function sendLessonOtp(Lesson $lesson, string $otp)
    {
        $message = "Your lesson start OTP: {$otp}. Valid for 15 minutes.";
        
        $this->queueSms($lesson->student->phone, $message, 'lesson_otp', $lesson->id);
    }

    public function sendRescheduleNotification(Lesson $lesson)
    {
        $message = "Lesson rescheduled to {$lesson->scheduled_at->format('Y-m-d H:i')}.";
        
        $this->queueSms($lesson->student->phone, $message, 'reschedule', $lesson->id);
        $this->queueSms($lesson->instructor->phone, $message, 'reschedule', $lesson->id);
    }

    public function sendCancellationNotification(Lesson $lesson)
    {
        $message = "Lesson on {$lesson->scheduled_at->format('Y-m-d H:i')} has been cancelled.";
        
        $this->queueSms($lesson->student->phone, $message, 'cancel', $lesson->id);
        $this->queueSms($lesson->instructor->phone, $message, 'cancel', $lesson->id);
    }

    protected function queueSms(string $phone, string $message, string $type, ?int $lessonId = null)
    {
        SmsNotification::create([
            'lesson_id' => $lessonId,
            'phone' => $phone,
            'type' => $type,
            'message' => $message,
            'status' => 'pending',
        ]);
    }
}

// app/Services/PaymentVerificationService.php
namespace App\Services;

use App\Models\PaymentDeposit;
use App\Models\Lesson;
use Carbon\Carbon;

class PaymentVerificationService
{
    public function verifyDeposit(PaymentDeposit $deposit): bool
    {
        // Simulate bank API call
        // In production, integrate with actual banking API
        $verified = $this->checkBankApi($deposit);

        if ($verified) {
            $deposit->update([
                'status' => 'confirmed',
                'verified_at' => Carbon::now(),
            ]);

            $deposit->lesson->update([
                'deposit_paid' => true,
                'status' => 'confirmed',
            ]);

            app(SmsService::class)->sendDepositConfirmed($deposit->lesson);

            return true;
        }

        return false;
    }

    protected function checkBankApi(PaymentDeposit $deposit): bool
    {
        // Mock implementation - replace with actual bank API
        // Check if payment with matching reference and amount exists
        return !empty($deposit->payid_reference);
    }
}

// ============================================
// CONTROLLERS
// ============================================

// app/Http/Controllers/Api/StudentController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\PaymentDeposit;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    protected $otpService;
    protected $smsService;

    public function __construct(OtpService $otpService, SmsService $smsService)
    {
        $this->otpService = $otpService;
        $this->smsService = $smsService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:students,phone',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::create([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        $otp = $this->otpService->generateStudentOtp($student);
        $this->smsService->queueSms($student->phone, "Your OTP: {$otp}", 'booking', null);

        return response()->json([
            'message' => 'OTP sent to your phone',
            'student_id' => $student->id,
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::findOrFail($request->student_id);
        
        if ($this->otpService->verifyStudentOtp($student, $request->otp)) {
            return response()->json(['message' => 'OTP verified successfully']);
        }

        return response()->json(['message' => 'Invalid or expired OTP'], 400);
    }

    public function bookLesson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'lesson_type_id' => 'required|exists:lesson_types,id',
            'instructor_id' => 'required|exists:instructors,id',
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check slot availability
        $conflict = Lesson::where('instructor_id', $request->instructor_id)
            ->where('scheduled_at', $request->scheduled_at)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Time slot not available'], 409);
        }

        $lesson = Lesson::create([
            'student_id' => $request->student_id,
            'lesson_type_id' => $request->lesson_type_id,
            'instructor_id' => $request->instructor_id,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending_deposit',
        ]);

        $lessonType = $lesson->lessonType;
        
        PaymentDeposit::create([
            'lesson_id' => $lesson->id,
            'amount' => $lessonType->price,
            'status' => 'pending',
        ]);

        $this->smsService->sendBookingConfirmation($lesson);

        return response()->json([
            'message' => 'Lesson booked successfully',
            'lesson' => $lesson->load(['lessonType', 'instructor']),
            'deposit_instructions' => [
                'payid' => 'school@payid.com.au',
                'amount' => $lessonType->price,
                'reference' => 'LESSON-' . $lesson->id,
            ],
        ], 201);
    }

    public function submitDeposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|exists:lessons,id',
            'payid_reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lesson = Lesson::findOrFail($request->lesson_id);
        $deposit = $lesson->paymentDeposit;

        if (!$deposit) {
            return response()->json(['message' => 'Deposit not found'], 404);
        }

        $deposit->update(['payid_reference' => $request->payid_reference]);

        return response()->json([
            'message' => 'Deposit reference submitted. Verification in progress.',
        ]);
    }

    public function rescheduleLesson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|exists:lessons,id',
            'new_scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lesson = Lesson::findOrFail($request->lesson_id);
        $lesson->update(['scheduled_at' => $request->new_scheduled_at]);

        $this->smsService->sendRescheduleNotification($lesson);

        return response()->json(['message' => 'Lesson rescheduled successfully']);
    }

    public function cancelLesson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|exists:lessons,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lesson = Lesson::findOrFail($request->lesson_id);
        $lesson->update(['status' => 'cancelled']);

        $this->smsService->sendCancellationNotification($lesson);

        return response()->json(['message' => 'Lesson cancelled successfully']);
    }

    public function lessonStatus($lessonId)
    {
        $lesson = Lesson::with(['lessonType', 'instructor', 'student', 'paymentDeposit'])
            ->findOrFail($lessonId);

        return response()->json(['lesson' => $lesson]);
    }
}

// app/Http/Controllers/Api/InstructorController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InstructorController extends Controller
{
    protected $otpService;
    protected $smsService;

    public function __construct(OtpService $otpService, SmsService $smsService)
    {
        $this->otpService = $otpService;
        $this->smsService = $smsService;
    }

    public function calendar(Request $request)
    {
        $instructorId = $request->query('instructor_id');
        $startDate = $request->query('start_date', Carbon::now()->startOfWeek());
        $endDate = $request->query('end_date', Carbon::now()->endOfWeek());

        $lessons = Lesson::where('instructor_id', $instructorId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with(['lessonType', 'student'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json(['lessons' => $lessons]);
    }

    public function lessonStartOtp(Request $request)
    {
        $lesson = Lesson::findOrFail($request->lesson_id);

        if ($lesson->status !== 'confirmed') {
            return response()->json(['message' => 'Lesson not confirmed'], 400);
        }

        $otp = $this->otpService->generateLessonOtp($lesson);
        $this->smsService->sendLessonOtp($lesson, $otp);

        return response()->json(['message' => 'OTP sent to student']);
    }

    public function verifyLessonOtp(Request $request)
    {
        $lesson = Lesson::findOrFail($request->lesson_id);
        
        if ($this->otpService->verifyLessonOtp($lesson, $request->otp)) {
            return response()->json(['message' => 'Lesson started successfully']);
        }

        return response()->json(['message' => 'Invalid or expired OTP'], 400);
    }
}

// app/Http/Controllers/Api/AdminController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Instructor;
use App\Models\LessonType;
use App\Models\PaymentDeposit;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentVerificationService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function dashboard()
    {
        $today = Carbon::today();
        
        $metrics = [
            'total_lessons_today' => Lesson::whereDate('scheduled_at', $today)->count(),
            'pending_deposits' => PaymentDeposit::where('status', 'pending')->count(),
            'completed_lessons_week' => Lesson::where('status', 'completed')
                ->whereBetween('scheduled_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->count(),
            'revenue_week' => PaymentDeposit::where('status', 'confirmed')
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->sum('amount'),
        ];

        return response()->json($metrics);
    }

    public function analytics(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->subDays(30));
        $endDate = $request->query('end_date', Carbon::now());

        $analytics = [
            'revenue' => PaymentDeposit::where('status', 'confirmed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'lessons_by_type' => Lesson::with('lessonType')
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->get()
                ->groupBy('lessonType.name')
                ->map->count(),
            'instructor_utilization' => Instructor::withCount(['lessons' => function($q) use ($startDate, $endDate) {
                    $q->whereBetween('scheduled_at', [$startDate, $endDate]);
                }])
                ->get(),
            'reschedules' => Lesson::where('status', 'cancelled')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
        ];

        return response()->json($analytics);
    }

    public function verifyDeposit(Request $request)
    {
        $deposit = PaymentDeposit::findOrFail($request->deposit_id);
        
        if ($this->paymentService->verifyDeposit($deposit)) {
            return response()->json(['message' => 'Deposit verified successfully']);
        }

        return response()->json(['message' => 'Deposit verification failed'], 400);
    }

    public function manageInstructor(Request $request)
    {
        if ($request->isMethod('post')) {
            $instructor = Instructor::create($request->all());
            return response()->json($instructor, 201);
        }

        if ($request->isMethod('put')) {
            $instructor = Instructor::findOrFail($request->id);
            $instructor->update($request->all());
            return response()->json($instructor);
        }

        if ($request->isMethod('delete')) {
            Instructor::findOrFail($request->id)->delete();
            return response()->json(['message' => 'Instructor deleted']);
        }
    }

    public function manageLessonType(Request $request)
    {
        if ($request->isMethod('post')) {
            $lessonType = LessonType::create($request->all());
            return response()->json($lessonType, 201);
        }

        if ($request->isMethod('put')) {
            $lessonType = LessonType::findOrFail($request->id);
            $lessonType->update($request->all());
            return response()->json($lessonType);
        }

        if ($request->isMethod('delete')) {
            LessonType::findOrFail($request->id)->delete();
            return response()->json(['message' => 'Lesson type deleted']);
        }
    }
}

// ============================================
// ROUTES
// ============================================

// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\AdminController;

// Student Routes
Route::prefix('student')->group(function () {
    Route::post('/register', [StudentController::class, 'register']);
    Route::post('/verify-otp', [StudentController::class, 'verifyOtp']);
    Route::post('/book-lesson', [StudentController::class, 'bookLesson']);
    Route::post('/submit-deposit', [StudentController::class, 'submitDeposit']);
    Route::post('/reschedule-lesson', [StudentController::class, 'rescheduleLesson']);
    Route::post('/cancel-lesson', [StudentController::class, 'cancelLesson']);
    Route::get('/lesson-status/{lessonId}', [StudentController::class, 'lessonStatus']);
});

// Instructor Routes
Route::prefix('instructor')->group(function () {
    Route::get('/calendar', [InstructorController::class, 'calendar']);
    Route::post('/lesson-start-otp', [InstructorController::class, 'lessonStartOtp']);
    Route::post('/verify-lesson-otp', [InstructorController::class, 'verifyLessonOtp']);
});

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/analytics', [AdminController::class, 'analytics']);
    Route::post('/verify-deposit', [AdminController::class, 'verifyDeposit']);
    Route::any('/manage-instructor', [AdminController::class, 'manageInstructor']);
    Route::any('/manage-lesson-type', [AdminController::class, 'manageLessonType']);
});

// ============================================
// CONSOLE COMMANDS (Scheduled Jobs)
// ============================================

// app/Console/Commands/VerifyPendingDeposits.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentDeposit;
use App\Services\PaymentVerificationService;

class VerifyPendingDeposits extends Command
{
    protected $signature = 'deposits:verify';
    protected $description = 'Verify pending payment deposits';

    public function handle(PaymentVerificationService $service)
    {
        $deposits = PaymentDeposit::where('status', 'pending')
            ->whereNotNull('payid_reference')
            ->get();

        foreach ($deposits as $deposit) {
            $service->verifyDeposit($deposit);
        }

        $this->info("Verified {$deposits->count()} deposits");
    }
}

// app/Console/Commands/SendSmsBatch.php
namespace App\Console\Commands;

use