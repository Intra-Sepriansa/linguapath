<?php

namespace App\Enums;

enum QuestionType: string
{
    case ShortConversation = 'short_conversation';
    case LongConversation = 'long_conversation';
    case TalksLectures = 'talks_lectures';
    case CampusConversation = 'campus_conversation';
    case SpeakerAttitude = 'speaker_attitude';
    case Function = 'function';
    case IncompleteSentence = 'incomplete_sentence';
    case ErrorRecognition = 'error_recognition';
    case SentenceCorrection = 'sentence_correction';
    case MainIdea = 'main_idea';
    case Detail = 'detail';
    case VocabularyContext = 'vocabulary_context';
    case Reference = 'reference';
    case Inference = 'inference';
    case AuthorPurpose = 'author_purpose';
    case SentenceInsertion = 'sentence_insertion';
    case Summary = 'summary';
    case ReadAloud = 'read_aloud';
    case Shadowing = 'shadowing';
    case Roleplay = 'roleplay';
    case WritingPrompt = 'writing_prompt';
}
