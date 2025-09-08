<?php

namespace App\Services;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Illuminate\Support\Facades\Log;

class Neo4jService
{
    private ClientInterface $client;
    private string $connectionString;

    public function __construct()
    {
        $this->connectionString = config('neo4j.connection_string', 'bolt://neo4j:password123@neo4j:7687');
        
        try {
            $this->client = ClientBuilder::create()
                ->withDriver('bolt', $this->connectionString)
                ->build();
        } catch (\Exception $e) {
            Log::error('Erreur de connexion Neo4j: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Synchroniser un enseignant avec ses compétences et certifications
     */
    public function syncTeacher($teacher): bool
    {
        try {
            $this->client->run('
                MERGE (t:Teacher {id: $teacherId})
                SET t.name = $name,
                    t.email = $email,
                    t.experience_years = $experienceYears,
                    t.club_id = $clubId,
                    t.specialization = $specialization,
                    t.last_sync = datetime()
            ', [
                'teacherId' => $teacher->id,
                'name' => $teacher->user->name ?? 'Enseignant',
                'email' => $teacher->user->email ?? '',
                'experienceYears' => $teacher->experience_years ?? 0,
                'clubId' => $teacher->club_id,
                'specialization' => $teacher->specialization ?? ''
            ]);

            // Synchroniser les compétences
            $this->syncTeacherSkills($teacher);
            
            // Synchroniser les certifications
            $this->syncTeacherCertifications($teacher);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur sync enseignant: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchroniser les compétences d'un enseignant
     */
    private function syncTeacherSkills($teacher): void
    {
        foreach ($teacher->skills as $teacherSkill) {
            $skill = $teacherSkill->skill;
            
            // Créer le nœud Skill
            $this->client->run('
                MERGE (s:Skill {id: $skillId})
                SET s.name = $name,
                    s.category = $category,
                    s.activity_type = $activityType,
                    s.description = $description
            ', [
                'skillId' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'activityType' => $skill->activityType->name ?? 'general',
                'description' => $skill->description ?? ''
            ]);

            // Créer la relation HAS_SKILL
            $this->client->run('
                MATCH (t:Teacher {id: $teacherId})
                MATCH (s:Skill {id: $skillId})
                MERGE (t)-[r:HAS_SKILL]->(s)
                SET r.level = $level,
                    r.experience_years = $experienceYears,
                    r.acquired_date = $acquiredDate,
                    r.last_practiced = $lastPracticed,
                    r.is_verified = $isVerified,
                    r.notes = $notes
            ', [
                'teacherId' => $teacher->id,
                'skillId' => $skill->id,
                'level' => $teacherSkill->level,
                'experienceYears' => $teacherSkill->experience_years,
                'acquiredDate' => $teacherSkill->acquired_date?->format('Y-m-d'),
                'lastPracticed' => $teacherSkill->last_practiced?->format('Y-m-d'),
                'isVerified' => $teacherSkill->is_verified,
                'notes' => $teacherSkill->notes ?? ''
            ]);
        }
    }

    /**
     * Synchroniser les certifications d'un enseignant
     */
    private function syncTeacherCertifications($teacher): void
    {
        foreach ($teacher->certifications as $teacherCert) {
            $certification = $teacherCert->certification;
            
            // Créer le nœud Certification
            $this->client->run('
                MERGE (c:Certification {id: $certId})
                SET c.name = $name,
                    c.authority = $authority,
                    c.category = $category,
                    c.activity_type = $activityType,
                    c.validity_years = $validityYears
            ', [
                'certId' => $certification->id,
                'name' => $certification->name,
                'authority' => $certification->issuing_authority,
                'category' => $certification->category,
                'activityType' => $certification->activityType->name ?? 'general',
                'validityYears' => $certification->validity_years
            ]);

            // Créer la relation HAS_CERTIFICATION
            $this->client->run('
                MATCH (t:Teacher {id: $teacherId})
                MATCH (c:Certification {id: $certId})
                MERGE (t)-[r:HAS_CERTIFICATION]->(c)
                SET r.obtained_date = $obtainedDate,
                    r.expiry_date = $expiryDate,
                    r.certificate_number = $certNumber,
                    r.is_valid = $isValid,
                    r.is_verified = $isVerified
            ', [
                'teacherId' => $teacher->id,
                'certId' => $certification->id,
                'obtainedDate' => $teacherCert->obtained_date?->format('Y-m-d'),
                'expiryDate' => $teacherCert->expiry_date?->format('Y-m-d'),
                'certNumber' => $teacherCert->certificate_number,
                'isValid' => $teacherCert->is_valid,
                'isVerified' => $teacherCert->is_verified
            ]);
        }
    }

    /**
     * Synchroniser un étudiant avec ses cours et progression
     */
    public function syncStudent($student): bool
    {
        try {
            $this->client->run('
                MERGE (s:Student {id: $studentId})
                SET s.name = $name,
                    s.email = $email,
                    s.level = $level,
                    s.club_id = $clubId,
                    s.objectives = $objectives,
                    s.last_sync = datetime()
            ', [
                'studentId' => $student->id,
                'name' => $student->user->name ?? 'Étudiant',
                'email' => $student->user->email ?? '',
                'level' => $student->level ?? 'beginner',
                'clubId' => $student->club_id,
                'objectives' => $student->objectives ?? ''
            ]);

            // Synchroniser les cours suivis
            $this->syncStudentLessons($student);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur sync étudiant: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchroniser les cours d'un étudiant
     */
    private function syncStudentLessons($student): void
    {
        foreach ($student->lessons as $lesson) {
            // Créer le nœud Lesson
            $this->client->run('
                MERGE (l:Lesson {id: $lessonId})
                SET l.title = $title,
                    l.discipline = $discipline,
                    l.date = $date,
                    l.duration = $duration,
                    l.price = $price,
                    l.status = $status
            ', [
                'lessonId' => $lesson->id,
                'title' => $lesson->title ?? 'Cours',
                'discipline' => $lesson->discipline->name ?? '',
                'date' => $lesson->start_time?->format('Y-m-d'),
                'duration' => $lesson->duration ?? 60,
                'price' => $lesson->price ?? 0,
                'status' => $lesson->status ?? 'completed'
            ]);

            // Créer la relation TAKES_LESSON
            $this->client->run('
                MATCH (s:Student {id: $studentId})
                MATCH (l:Lesson {id: $lessonId})
                MERGE (s)-[r:TAKES_LESSON]->(l)
                SET r.rating = $rating,
                    r.feedback = $feedback,
                    r.progress = $progress
            ', [
                'studentId' => $student->id,
                'lessonId' => $lesson->id,
                'rating' => $lesson->rating ?? 0,
                'feedback' => $lesson->feedback ?? '',
                'progress' => $lesson->progress ?? 0
            ]);

            // Lier l'enseignant au cours
            if ($lesson->teacher) {
                $this->client->run('
                    MATCH (t:Teacher {id: $teacherId})
                    MATCH (l:Lesson {id: $lessonId})
                    MERGE (t)-[r:TEACHES]->(l)
                ', [
                    'teacherId' => $lesson->teacher->id,
                    'lessonId' => $lesson->id
                ]);
            }
        }
    }

    /**
     * Trouver des enseignants correspondants à un étudiant
     */
    public function findMatchingTeachers($student, $requirements = []): array
    {
        $query = '
            MATCH (s:Student {id: $studentId})
            MATCH (t:Teacher)-[:HAS_SKILL]->(sk:Skill)
            WHERE sk.name IN $requiredSkills
            WITH t, count(sk) as skillMatch
            MATCH (t)-[:HAS_CERTIFICATION]->(c:Certification)
            WHERE c.is_valid = true
            RETURN t.id as teacher_id, 
                   t.name as name, 
                   t.experience_years as experience,
                   skillMatch,
                   collect(DISTINCT sk.name) as skills,
                   collect(DISTINCT c.name) as certifications
            ORDER BY skillMatch DESC, t.experience_years DESC
        ';

        $result = $this->client->run($query, [
            'studentId' => $student->id,
            'requiredSkills' => $requirements['skills'] ?? []
        ]);

        return $result->toArray();
    }

    /**
     * Analyser les performances d'un enseignant
     */
    public function analyzeTeacherPerformance($teacherId): array
    {
        $query = '
            MATCH (t:Teacher {id: $teacherId})
            MATCH (t)-[:TEACHES]->(l:Lesson)
            MATCH (s:Student)-[:TAKES_LESSON]->(l)
            WITH t, l, s, l.rating as rating, l.progress as progress
            RETURN t.name as teacher_name,
                   count(l) as total_lessons,
                   avg(rating) as avg_rating,
                   avg(progress) as avg_progress,
                   count(DISTINCT s) as unique_students,
                   collect(DISTINCT l.discipline) as disciplines
        ';

        $result = $this->client->run($query, ['teacherId' => $teacherId]);
        return $result->toArray();
    }

    /**
     * Obtenir des recommandations pour un enseignant
     */
    public function getTeacherRecommendations($teacherId): array
    {
        $query = '
            MATCH (t:Teacher {id: $teacherId})
            MATCH (t)-[:HAS_SKILL]->(sk:Skill)
            MATCH (other:Teacher)-[:HAS_SKILL]->(sk)
            WHERE other.id <> $teacherId
            WITH other, count(sk) as commonSkills
            MATCH (other)-[:HAS_SKILL]->(otherSk:Skill)
            WHERE NOT (t)-[:HAS_SKILL]->(otherSk)
            RETURN other.id as teacher_id,
                   other.name as name,
                   commonSkills,
                   collect(DISTINCT otherSk.name) as suggested_skills
            ORDER BY commonSkills DESC
            LIMIT 5
        ';

        $result = $this->client->run($query, ['teacherId' => $teacherId]);
        return $result->toArray();
    }

    /**
     * Analyser le réseau de compétences
     */
    public function analyzeSkillsNetwork(): array
    {
        $query = '
            MATCH (t:Teacher)-[:HAS_SKILL]->(s:Skill)
            WITH s, count(t) as teacherCount
            MATCH (s)<-[:HAS_SKILL]-(t2:Teacher)-[:HAS_SKILL]->(s2:Skill)
            WHERE s <> s2
            RETURN s.name as skill,
                   teacherCount,
                   collect(DISTINCT s2.name) as related_skills
            ORDER BY teacherCount DESC
        ';

        $result = $this->client->run($query);
        return $result->toArray();
    }

    /**
     * Prédire la réussite d'un étudiant
     */
    public function predictStudentSuccess($studentId): array
    {
        $query = '
            MATCH (s:Student {id: $studentId})
            MATCH (s)-[:TAKES_LESSON]->(l:Lesson)
            MATCH (similar:Student)-[:TAKES_LESSON]->(similarLesson:Lesson)
            WHERE similar.id <> $studentId 
            AND similarLesson.discipline = l.discipline
            WITH s, avg(similarLesson.progress) as avgProgress
            MATCH (s)-[:TAKES_LESSON]->(recent:Lesson)
            WHERE recent.date > date() - duration("P30D")
            RETURN s.name as student_name,
                   avgProgress as predicted_progress,
                   count(recent) as recent_lessons,
                   avg(recent.progress) as current_progress
        ';

        $result = $this->client->run($query, ['studentId' => $studentId]);
        return $result->toArray();
    }

    /**
     * Synchroniser toutes les données
     */
    public function syncAllData(): array
    {
        $results = [
            'teachers' => 0,
            'students' => 0,
            'errors' => []
        ];

        // Synchroniser tous les enseignants
        $teachers = \App\Models\Teacher::with(['user', 'skills.skill', 'certifications.certification'])->get();
        foreach ($teachers as $teacher) {
            if ($this->syncTeacher($teacher)) {
                $results['teachers']++;
            } else {
                $results['errors'][] = "Erreur sync enseignant {$teacher->id}";
            }
        }

        // Synchroniser tous les étudiants
        $students = \App\Models\Student::with(['user', 'lessons'])->get();
        foreach ($students as $student) {
            if ($this->syncStudent($student)) {
                $results['students']++;
            } else {
                $results['errors'][] = "Erreur sync étudiant {$student->id}";
            }
        }

        return $results;
    }

    /**
     * Vérifier la connexion Neo4j
     */
    public function checkConnection(): bool
    {
        try {
            $result = $this->client->run('RETURN 1 as test');
            return !empty($result->toArray());
        } catch (\Exception $e) {
            Log::error('Erreur connexion Neo4j: ' . $e->getMessage());
            return false;
        }
    }
}
