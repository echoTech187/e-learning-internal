<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\QuizModel;
use App\Models\QuestionModel;
use App\Models\QuestionOptionModel;
use App\Models\QuizAttemptModel;
use App\Models\QuizAnswerModel;

/**
 * Quizzes Controller (Internal)
 *
 * GET  /api/quizzes/{quizId}?user_id={userId}     -> Fetch quiz + questions + attempt history
 * POST /api/quizzes/{quizId}/start                 -> Start a new attempt
 * POST /api/quizzes/{quizId}/submit                -> Submit answers & get result
 * GET  /api/quizzes/{quizId}/result/{attemptId}   -> Get detailed result for an attempt
 */
class Quizzes extends ResourceController
{
    /** GET /api/quizzes/{quizId} */
    public function show($quizId = null)
    {
        $userId = $this->request->getGet('user_id');
        $quizModel = new QuizModel();
        // Coba cari berdasarkan quiz ID dulu (primary key)
        $quiz      = $quizModel->find($quizId);
        
        // Jika tidak ketemu, coba cari berdasarkan lesson_id (karena frontend mem-passing lesson.id sebagai quizId)
        if (!$quiz) {
            $quiz = $quizModel->where('lesson_id', $quizId)->first();
        }

        if (!$quiz || !$quiz['is_active']) {
            return $this->failNotFound('Quiz not found');
        }

        $questionModel = new QuestionModel();
        $optionModel   = new QuestionOptionModel();

        $questions = $questionModel
            ->where('quiz_id', $quizId)
            ->orderBy('order', 'ASC')
            ->findAll();

        foreach ($questions as &$q) {
            // Only send is_correct for essay review; hide for multiple_choice
            $options = $optionModel
                ->where('question_id', $q['id'])
                ->orderBy('order', 'ASC')
                ->findAll();

            // Shuffle if quiz requires it
            if ($quiz['shuffle']) {
                shuffle($options);
            }

            // Strip correct answer flags before sending to client
            $q['options'] = array_map(function ($opt) {
                return [
                    'id'          => $opt['id'],
                    'option_text' => $opt['option_text'],
                ];
            }, $options);
        }

        // Attempt history for this user
        $attemptHistory = [];
        if ($userId) {
            $attemptModel   = new QuizAttemptModel();
            $attemptHistory = $attemptModel
                ->where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->orderBy('created_at', 'DESC')
                ->findAll();
        }

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'quiz'            => $quiz,
                'questions'       => $questions,
                'attempt_history' => $attemptHistory,
                'attempts_used'   => count($attemptHistory),
                'can_attempt'     => count($attemptHistory) < (int) $quiz['max_attempts'],
            ],
        ]);
    }

    /** POST /api/quizzes/{quizId}/start */
    public function start($quizId = null)
    {
        $json   = $this->request->getJSON();
        $userId = $json->user_id ?? null;

        if (!$userId) return $this->failValidationErrors('user_id is required');
        $quizModel = new QuizModel();
        // Coba cari berdasarkan quiz ID dulu (primary key)
        $quiz      = $quizModel->find($quizId);
        
        // Jika tidak ketemu, coba cari berdasarkan lesson_id (karena frontend mem-passing lesson.id sebagai quizId)
        if (!$quiz) {
            $quiz = $quizModel->where('lesson_id', $quizId)->first();
        }

        if (!$quiz || !$quiz['is_active']) {
            return $this->failNotFound('Quiz not found');
        }

        $attemptModel   = new QuizAttemptModel();
        $existingCount  = $attemptModel
            ->where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->countAllResults();

        if ($existingCount >= (int) $quiz['max_attempts']) {
            return $this->fail('Maximum attempts reached', 422);
        }

        $now       = date('Y-m-d H:i:s');
        $attemptId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        $attemptModel->insert([
            'id'           => $attemptId,
            'user_id'      => $userId,
            'quiz_id'      => $quizId,
            'score'        => 0,
            'total_points' => 0,
            'earned_points'=> 0,
            'is_passed'    => 0,
            'started_at'   => $now,
            'created_at'   => $now,
        ]);

        return $this->respondCreated([
            'status' => 'success',
            'data'   => ['attempt_id' => $attemptId, 'started_at' => $now],
        ]);
    }

    /** POST /api/quizzes/{quizId}/submit */
    public function submit($quizId = null)
    {
        $json      = $this->request->getJSON();
        $userId    = $json->user_id    ?? null;
        $attemptId = $json->attempt_id ?? null;
        $answers   = $json->answers    ?? []; // [{question_id, answer (option_id or text)}]

        if (!$userId || !$attemptId) {
            return $this->failValidationErrors('user_id and attempt_id are required');
        }

        $attemptModel = new QuizAttemptModel();
        $attempt      = $attemptModel->find($attemptId);

        if (!$attempt || $attempt['user_id'] !== $userId || $attempt['quiz_id'] !== $quizId) {
            return $this->failNotFound('Attempt not found');
        }

        if ($attempt['finished_at']) {
            return $this->fail('Attempt already submitted', 422);
        }
        $quizModel = new QuizModel();
        // Coba cari berdasarkan quiz ID dulu (primary key)
        $quiz      = $quizModel->find($quizId);
        
        // Jika tidak ketemu, coba cari berdasarkan lesson_id (karena frontend mem-passing lesson.id sebagai quizId)
        if (!$quiz) {
            $quiz = $quizModel->where('lesson_id', $quizId)->first();
        }

        $questionModel = new QuestionModel();
        $optionModel   = new QuestionOptionModel();
        $answerModel   = new QuizAnswerModel();

        $questions    = $questionModel->where('quiz_id', $quizId)->findAll();
        $questionMap  = array_column($questions, null, 'id');

        $totalPoints  = 0;
        $earnedPoints = 0;
        $answerRecords= [];

        foreach ($answers as $ans) {
            $qId       = $ans->question_id ?? null;
            $userAnswer= $ans->answer      ?? null;
            if (!$qId || !isset($questionMap[$qId])) continue;

            $q       = $questionMap[$qId];
            $isCorrect = false;

            if ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false') {
                // Check if the selected option_id is the correct one
                $correctOption = $optionModel
                    ->where('question_id', $qId)
                    ->where('is_correct', 1)
                    ->first();
                if ($correctOption && $correctOption['id'] === $userAnswer) {
                    $isCorrect = true;
                }
            }
            // essay: always marked false automatically; teacher grades manually

            $totalPoints  += (int) $q['points'];
            if ($isCorrect) $earnedPoints += (int) $q['points'];

            $answerRecords[] = [
                'id'          => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'attempt_id'  => $attemptId,
                'question_id' => $qId,
                'answer'      => $userAnswer,
                'is_correct'  => $isCorrect ? 1 : 0,
            ];
        }

        // Batch insert answers
        if (!empty($answerRecords)) {
            $answerModel->insertBatch($answerRecords);
        }

        $score    = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $isPassed = $score >= (int) $quiz['passing_score'];
        $now      = date('Y-m-d H:i:s');

        $attemptModel->update($attemptId, [
            'score'         => $score,
            'total_points'  => $totalPoints,
            'earned_points' => $earnedPoints,
            'is_passed'     => $isPassed ? 1 : 0,
            'finished_at'   => $now,
        ]);

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'attempt_id'    => $attemptId,
                'score'         => $score,
                'total_points'  => $totalPoints,
                'earned_points' => $earnedPoints,
                'is_passed'     => $isPassed,
                'passing_score' => (int) $quiz['passing_score'],
                'finished_at'   => $now,
            ],
        ]);
    }

    /** GET /api/quizzes/{quizId}/result/{attemptId} */
    public function result($quizId = null, $attemptId = null)
    {
        $userId = $this->request->getGet('user_id');

        $attemptModel = new QuizAttemptModel();
        $attempt      = $attemptModel->find($attemptId);

        if (!$attempt || $attempt['user_id'] !== $userId || $attempt['quiz_id'] !== $quizId) {
            return $this->failNotFound('Attempt not found');
        }

        $answerModel    = new QuizAnswerModel();
        $questionModel  = new QuestionModel();
        $optionModel    = new QuestionOptionModel();

        $answers   = $answerModel->where('attempt_id', $attemptId)->findAll();
        $questions = $questionModel->where('quiz_id', $quizId)->orderBy('order', 'ASC')->findAll();

        $enriched = [];
        foreach ($questions as $q) {
            $options = $optionModel->where('question_id', $q['id'])->orderBy('order', 'ASC')->findAll();

            // Find user answer
            $userAnswer = null;
            foreach ($answers as $a) {
                if ($a['question_id'] === $q['id']) {
                    $userAnswer = $a;
                    break;
                }
            }

            $enriched[] = [
                'question'    => $q,
                'options'     => $options,
                'user_answer' => $userAnswer,
            ];
        }
        $quizModel = new QuizModel();
        // Coba cari berdasarkan quiz ID dulu (primary key)
        $quiz      = $quizModel->find($quizId);
        
        // Jika tidak ketemu, coba cari berdasarkan lesson_id (karena frontend mem-passing lesson.id sebagai quizId)
        if (!$quiz) {
            $quiz = $quizModel->where('lesson_id', $quizId)->first();
        }

        return $this->respond([
            'status' => 'success',
            'data'   => [
                'quiz'    => $quiz,
                'attempt' => $attempt,
                'review'  => $enriched,
            ],
        ]);
    }
}
