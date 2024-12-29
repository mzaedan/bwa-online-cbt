<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\CourseQuestion;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $my_courses = $user->courses()->with('category')->orderBy('id', 'DESC')->get();

        foreach($my_courses as $course){
            $totalQuestionCount = $course->questions()->count();

            $answeredQuestionCount = StudentAnswer::where('user_id', $user->id)->whereHas('question', function ($query) use ($course){
                $query->where('course_id', $course->id);
            })->distinct()->count('course_question_id');

            if($answeredQuestionCount < $totalQuestionCount){
                $firstUnansweredQuestion = CourseQuestion::where('course_id', $course->id)
                ->whereNotIn('id', function($query) use ($user){
                    $query->select('course_question_id')->from('student_answers')
                    ->where('user_id', $user->id);
                })->orderBy('id', 'asc')->first();

                $course->nextQuestionId = $firstUnansweredQuestion ? $firstUnansweredQuestion->id : null;
            }
            else {
                $course->nextQuestionId = null;
            }  
        }
        
        return view('student.courses.index', [
            'my_courses' => $my_courses,
        ]);
    }

    public function learning(Course $course, $question){
        $user = Auth::user();

        $isEnrroled = $user->courses()->where('course_id', $course->id)->exists();

        if(!$isEnrroled){
            abort(404);
        }

        $currentQuestion = CourseQuestion::where('course_id', $course->id)->where('id', $question)->firstOrFail();

        return view('student.courses.learning', [
            'course' => $course,
            'question' => $currentQuestion
        ]);
    }
}
