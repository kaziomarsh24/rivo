<?php

namespace Database\Seeders;

use App\Models\Pet;
use App\Models\PetTrainingProgress;
use App\Models\PetTrainingStat;
use App\Models\TrainingCategory;
use App\Models\TrainingLesson;
use App\Models\TrainingLessonStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Training Categories matching Figma
        $categoriesData = [
            [
                'name' => 'Basic Commands',
                'slug' => 'basic-commands',
                'subtitle' => 'Foundational obedience skills',
                'icon' => 'training/icons/basic.png',
                'order_column' => 1,
            ],
            [
                'name' => 'Walking',
                'slug' => 'walking',
                'subtitle' => 'Leash manners and outdoor focus',
                'icon' => 'training/icons/walking.png',
                'order_column' => 2,
            ],
            [
                'name' => 'Tricks',
                'slug' => 'tricks',
                'subtitle' => 'Fun and engaging mental exercises',
                'icon' => 'training/icons/tricks.png',
                'order_column' => 3,
            ],
            [
                'name' => 'Behavior',
                'slug' => 'behavior',
                'subtitle' => 'Crate comfort, impulse control, and calm',
                'icon' => 'training/icons/behavior.png',
                'order_column' => 4,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = TrainingCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        // 2. Create Lessons with Step-by-Step Guides
        $lessonsData = [
            // Category: Basic Commands (4 Lessons)
            [
                'category_slug' => 'basic-commands',
                'title' => 'Teach "Sit" Command',
                'tag' => 'TRAINING Short',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 10,
                'impact' => 'Low impact',
                'reward_tp' => 2,
                'reward_xp' => 20,
                'summary' => 'A great confidence-builder for your pet.',
                'description' => 'The foundational building block of all obedience. Teaches your pet to focus on handler cues.',
                'order_column' => 1,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => "Get your pet's attention",
                        'instruction' => 'Hold a small, high-value treat right in front of your dog\'s nose so they focus entirely on you.',
                        'tip' => 'Use a quiet room with minimal distractions for initial repetitions.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Lure into sit position',
                        'instruction' => 'Slowly move your hand up and back over their head. As their nose tracks the treat upward, their bottom will naturally lower to the ground.',
                        'tip' => 'Keep the treat close to their nose—holding it too high may cause them to jump instead.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Mark and reward',
                        'instruction' => 'The exact second their bottom touches the floor, say "Yes!" enthusiastically and release the treat.',
                        'tip' => 'Repeat 5 to 8 times per session. End each session on a high note!',
                    ],
                ],
            ],
            [
                'category_slug' => 'basic-commands',
                'title' => 'Stay Command',
                'tag' => 'TRAINING Short',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 10,
                'impact' => 'Low impact',
                'reward_tp' => 2,
                'reward_xp' => 20,
                'summary' => 'Essential for impulse control and safety.',
                'description' => 'Teaches your dog to remain completely motionless until released by a cue word.',
                'order_column' => 2,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Ask for a steady sit',
                        'instruction' => 'Have your dog sit comfortably facing you before introducing the stay cue.',
                        'tip' => 'Make sure they are fully settled before stepping back.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Present hand signal',
                        'instruction' => 'Hold your flat palm facing them like a stop sign and say "Stay" in a calm, firm tone.',
                        'tip' => 'Keep your posture steady and calm.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Take one step back',
                        'instruction' => 'Take a single step back, pause for 2 seconds, then step right back to them, mark "Yes!", and reward.',
                        'tip' => 'Gradually increase duration before increasing distance.',
                    ],
                ],
            ],
            [
                'category_slug' => 'basic-commands',
                'title' => 'Come (Reliable Recall)',
                'tag' => 'TRAINING Short',
                'species' => 'dog',
                'difficulty' => 'intermediate',
                'duration_minutes' => 15,
                'impact' => 'Medium impact',
                'reward_tp' => 3,
                'reward_xp' => 30,
                'summary' => 'Life-saving recall command for outdoor safety.',
                'description' => 'A strong recall cue ensures your dog returns enthusiastically whenever called.',
                'order_column' => 3,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Crouch down and invite',
                        'instruction' => 'Crouch down slightly to appear friendly, open your arms, and call their name followed by "Come!"',
                        'tip' => 'Use a high-pitched, excited tone.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Praise on the way',
                        'instruction' => 'As your pet dashes toward you, cheer them on enthusiastically so they run with full velocity.',
                        'tip' => 'Never call your pet to come to do something they dislike (e.g. nail trimming).',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Jackpot treat reward',
                        'instruction' => 'When they arrive, hold their collar gently and feed 3-4 treats consecutively to make recall a celebration.',
                        'tip' => 'Holding their collar briefly prevents them from darting away immediately.',
                    ],
                ],
            ],
            [
                'category_slug' => 'basic-commands',
                'title' => 'Down (Lie Down)',
                'tag' => 'TRAINING Short',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 10,
                'impact' => 'Low impact',
                'reward_tp' => 2,
                'reward_xp' => 20,
                'summary' => 'Promotes calm relaxation and ground settling.',
                'description' => 'A relaxed down command signals downtime and self-soothing in busy environments.',
                'order_column' => 4,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Lure from sit to floor',
                        'instruction' => 'From a sitting position, hold a treat at their nose and lower it vertically straight down between their front paws.',
                        'tip' => 'If they stand up instead of lying down, reset gently to a sit.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Pull slightly forward',
                        'instruction' => 'Once the treat touches the floor, slide your hand along the ground slowly toward you.',
                        'tip' => 'Their paws will slide forward into a comfortable sphinx pose.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Reward on elbows down',
                        'instruction' => 'The instant their elbows touch the floor, praise enthusiastically and deliver the treat right on the ground.',
                        'tip' => 'Feeding on the ground reinforces staying low.',
                    ],
                ],
            ],

            // Category: Walking (3 Lessons)
            [
                'category_slug' => 'walking',
                'title' => 'Loose Leash Walking',
                'tag' => 'WALK',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 15,
                'impact' => 'Medium impact',
                'reward_tp' => 3,
                'reward_xp' => 30,
                'summary' => 'Eliminates pulling and builds relaxed neighborhood strolls.',
                'description' => 'Teach your pet that a loose J-shaped leash allows progress, while tension means stopping.',
                'order_column' => 1,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Be a tree when leash tightens',
                        'instruction' => 'The exact moment the leash goes taut, freeze completely in place. Do not yank or jerk back.',
                        'tip' => 'Consistency is key—never let pulling bring them even one inch closer to a smell.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Wait for eye contact or slack',
                        'instruction' => 'Wait patiently until your dog pauses, turns, or relieves leash tension.',
                        'tip' => 'Even a tiny glance back counts as progress.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Resume walking with treat delivery',
                        'instruction' => 'Mark "Yes!" and feed a treat right beside your pant seam, then take 3 relaxed steps forward.',
                        'tip' => 'Rewarding next to your leg teaches the sweet spot where walking happens.',
                    ],
                ],
            ],
            [
                'category_slug' => 'walking',
                'title' => 'Heel Position',
                'tag' => 'WALK',
                'species' => 'dog',
                'difficulty' => 'intermediate',
                'duration_minutes' => 15,
                'impact' => 'Medium impact',
                'reward_tp' => 4,
                'reward_xp' => 35,
                'summary' => 'Structured walking tightly beside handler on busy sidewalks.',
                'description' => 'Precision positioning for crowded city walking, street crossings, and tight spaces.',
                'order_column' => 2,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Align pet at left hip',
                        'instruction' => 'Have your dog stand parallel to you on your left side with their shoulder lined up with your knee.',
                        'tip' => 'Keep treats prepped in your left hand for rapid delivery.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Step forward with left foot',
                        'instruction' => 'Say "Heel" and step off with your left foot while holding a lure just ahead of their nose.',
                        'tip' => 'Taking your left foot forward first serves as a visual movement cue.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Continuous reinforcement',
                        'instruction' => 'Feed every 2 steps initially, gradually fading the lure into an upright hand signal.',
                        'tip' => 'Use brief 3-minute heel bursts followed by a "Free" release cue.',
                    ],
                ],
            ],
            [
                'category_slug' => 'walking',
                'title' => 'Passing Other Dogs Calmly',
                'tag' => 'WALK',
                'species' => 'dog',
                'difficulty' => 'intermediate',
                'duration_minutes' => 20,
                'impact' => 'High impact',
                'reward_tp' => 5,
                'reward_xp' => 40,
                'summary' => 'Neutral focus and calm passing during outdoor encounters.',
                'description' => 'Prevents leash reactivity and helps dogs ignore passing pedestrians, bikes, and neighborhood dogs.',
                'order_column' => 3,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Create distance buffer',
                        'instruction' => 'Spot oncoming dogs early and create at least 10 feet of space (e.g. crossing to sidewalk buffer).',
                        'tip' => 'Staying under threshold prevents barking before it starts.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Play the "Look at That" game',
                        'instruction' => 'When your dog notices the other dog, mark "Yes!" immediately before they react, and feed a treat.',
                        'tip' => 'This changes their emotional response from arousal to positive focus on you.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Keep moving past',
                        'instruction' => 'Feed continuous small treats as you briskly walk past, then praise calmly.',
                        'tip' => 'Celebrate calm neutrality—passing without pulling is a major win!',
                    ],
                ],
            ],

            // Category: Tricks (3 Lessons)
            [
                'category_slug' => 'tricks',
                'title' => 'Shake / High Five',
                'tag' => 'TRICK',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 10,
                'impact' => 'Low impact',
                'reward_tp' => 2,
                'reward_xp' => 20,
                'summary' => 'Cute social trick that delights friends and guests.',
                'description' => 'Builds paw awareness and positive handler touch through a fun interactive greeting.',
                'order_column' => 1,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Enclose treat in closed fist',
                        'instruction' => 'With dog in a sit, hold a closed fist with a treat right at their chest height.',
                        'tip' => 'Let them sniff your closed fingers to arouse curiosity.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Wait for natural paw touch',
                        'instruction' => 'When your dog raises their paw to scratch or nudge your hand, say "Shake!" and immediately open your palm to give the treat.',
                        'tip' => 'Catch the earliest moment of paw movement.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Fade the closed fist to open palm',
                        'instruction' => 'Switch to an open hand presented vertically for a "High Five" cue and reward consistently.',
                        'tip' => 'Teach both left and right paws for extra balance!',
                    ],
                ],
            ],
            [
                'category_slug' => 'tricks',
                'title' => 'Roll Over',
                'tag' => 'TRICK',
                'species' => 'dog',
                'difficulty' => 'intermediate',
                'duration_minutes' => 15,
                'impact' => 'Low impact',
                'reward_tp' => 4,
                'reward_xp' => 35,
                'summary' => 'Crowd-pleaser trick building full body coordination.',
                'description' => 'Guide your dog from a relaxed down position through a smooth 360-degree roll.',
                'order_column' => 2,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Start in a flat down',
                        'instruction' => 'Have your dog lie flat down on a soft rug or carpet where they feel secure.',
                        'tip' => 'Avoid hard slippery floors for rolling exercises.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Lure toward their shoulder',
                        'instruction' => 'Hold treat at nose and roll your wrist toward their shoulder blade so they lean onto their side.',
                        'tip' => 'Reward the side posture first before expecting a full revolution.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Complete the circular arc',
                        'instruction' => 'Continue tracing a smooth circular arc over their back toward the opposite side to roll them over.',
                        'tip' => 'Say "Roll Over" as momentum carries them through.',
                    ],
                ],
            ],
            [
                'category_slug' => 'tricks',
                'title' => 'Spin (Left & Right)',
                'tag' => 'TRICK',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 10,
                'impact' => 'Low impact',
                'reward_tp' => 2,
                'reward_xp' => 20,
                'summary' => 'Energizing agility warmup and tail-chasing fun.',
                'description' => 'Teaches your dog to follow a tight circular hand cue in a complete 360-degree pirouette.',
                'order_column' => 3,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Lure in a big circle',
                        'instruction' => 'From a standing position, hold treat at nose and guide their head in a wide circle around their body.',
                        'tip' => 'Keep the lure moving at steady walking pace.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Mark at the starting point',
                        'instruction' => 'When they complete the circle and face you again, mark "Yes!" and feed.',
                        'tip' => 'Use "Spin" for clockwise and "Twist" for counter-clockwise.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Shrink hand movement',
                        'instruction' => 'Gradually turn the big circular arm movement into a small finger flick overhead.',
                        'tip' => 'Dogs pick up visual gestures very quickly.',
                    ],
                ],
            ],

            // Category: Behavior (3 Lessons)
            [
                'category_slug' => 'behavior',
                'title' => 'Crate Training Basics',
                'tag' => 'BEHAVIOR',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 20,
                'impact' => 'Low impact',
                'reward_tp' => 2,
                'reward_xp' => 25,
                'summary' => 'Creates a safe, comforting den environment.',
                'description' => 'Helps pets associate the crate with security, restful sleep, and positive treats.',
                'order_column' => 1,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Open door welcome',
                        'instruction' => 'Keep crate door securely propped open with comfortable blanket and toss high-value treats inside.',
                        'tip' => 'Never force or push your pet inside—let them explore at their own pace.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Feed regular meals inside',
                        'instruction' => 'Place breakfast and dinner bowls inside the back of the crate to build a powerful positive connection.',
                        'tip' => 'Leave door wide open while eating.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Short 30-second closures',
                        'instruction' => 'Give a long-lasting chew toy, gently close the door for 30 seconds while sitting nearby, then quietly open.',
                        'tip' => 'Never release while barking or whining—wait for 3 seconds of calm silence.',
                    ],
                ],
            ],
            [
                'category_slug' => 'behavior',
                'title' => 'Leave It (Impulse Control)',
                'tag' => 'BEHAVIOR',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 10,
                'impact' => 'Low impact',
                'reward_tp' => 2,
                'reward_xp' => 25,
                'summary' => 'Crucial command preventing pets from eating dangerous objects.',
                'description' => 'Teaches your dog that ignoring a forbidden object immediately unlocks something far better.',
                'order_column' => 2,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Treat under your shoe or palm',
                        'instruction' => 'Place a basic kibble piece on the floor under your sneaker. Say "Leave it" when they sniff at your shoe.',
                        'tip' => 'Keep your foot firmly in place.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Reward head turn away',
                        'instruction' => 'The instant your dog stops sniffing your shoe and looks up at you, say "Yes!" and feed a higher-value treat from your hand.',
                        'tip' => 'Never feed the item on the floor—reward comes from your hand.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Advance to open floor item',
                        'instruction' => 'Practice with treat on open floor, ready to cover with hand if they lunge. Reward for eye contact.',
                        'tip' => 'Mastering "Leave It" keeps walks safe from toxic street items.',
                    ],
                ],
            ],
            [
                'category_slug' => 'behavior',
                'title' => 'Quiet Cue (Barking Management)',
                'tag' => 'BEHAVIOR',
                'species' => 'dog',
                'difficulty' => 'intermediate',
                'duration_minutes' => 15,
                'impact' => 'Medium impact',
                'reward_tp' => 3,
                'reward_xp' => 30,
                'summary' => 'Manages doorbell alert barking and window reactivity.',
                'description' => 'Acknowledge alert barks and redirect attention smoothly onto a calm resting spot.',
                'order_column' => 3,
                'steps' => [
                    [
                        'step_number' => 1,
                        'title' => 'Allow 1-2 alert barks',
                        'instruction' => 'Acknowledge the alert with "Thank you" so your dog knows you have checked the situation.',
                        'tip' => 'Yelling "Stop barking!" sounds like barking along to dogs.',
                    ],
                    [
                        'step_number' => 2,
                        'title' => 'Say "Quiet" and hold high-value treat',
                        'instruction' => 'Hold a smelly treat right at their nose. Dogs cannot sniff and bark simultaneously.',
                        'tip' => 'As soon as sniffing starts, the barking stops.',
                    ],
                    [
                        'step_number' => 3,
                        'title' => 'Reward 3 seconds of calm',
                        'instruction' => 'Wait for 3 seconds of silence, say "Yes!" and deliver the treat away from the window/door.',
                        'tip' => 'Redirect them to their dog bed for a chew toy.',
                    ],
                ],
            ],
        ];

        foreach ($lessonsData as $data) {
            $catSlug = $data['category_slug'];
            $steps = $data['steps'];
            unset($data['category_slug'], $data['steps']);

            $data['category_id'] = $categories[$catSlug]->id;
            $data['slug'] = Str::slug($data['title']);

            $lesson = TrainingLesson::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            // Create steps
            foreach ($steps as $stepData) {
                TrainingLessonStep::firstOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                        'step_number' => $stepData['step_number'],
                    ],
                    $stepData
                );
            }
        }

        // 3. Seed Pet Training Gamification stats for seeded pets
        $pets = Pet::all();
        foreach ($pets as $index => $pet) {
            $initialTp = ($index === 0) ? 700 : rand(50, 450); // Give 1st pet 700 TP to match Figma's Gold III screen!
            $stat = PetTrainingStat::firstOrCreate(
                ['pet_id' => $pet->id],
                [
                    'total_tp' => $initialTp,
                    'current_rank' => ($initialTp >= 700) ? 'Gold III' : 'Bronze II',
                    'current_streak_days' => ($index === 0) ? 5 : rand(1, 3),
                    'longest_streak_days' => ($index === 0) ? 12 : rand(3, 7),
                    'last_training_date' => now()->toDateString(),
                ]
            );

            // Complete first lesson for Pedro (first pet) so completed UI state is verified
            if ($index === 0) {
                $sitLesson = TrainingLesson::where('slug', 'teach-sit-command')->first();
                if ($sitLesson) {
                    PetTrainingProgress::firstOrCreate(
                        [
                            'pet_id' => $pet->id,
                            'lesson_id' => $sitLesson->id,
                        ],
                        [
                            'status' => 'completed',
                            'completed_at' => now()->subHours(4),
                            'tp_earned' => $sitLesson->reward_tp,
                            'xp_earned' => $sitLesson->reward_xp,
                        ]
                    );
                }
            }
        }
    }
}

