import {
    FileSearch,
    Languages,
    Lightbulb,
    UserCheck,
} from '@lucide/vue';
import type { AIAssistantJobDefinition } from './types';

export const AI_ASSISTANT_JOBS: AIAssistantJobDefinition[] = [
    {
        key: 'analyze_inquiry',
        label: 'Analyze inquiry',
        description: 'Summarize the inquiry, key facts, risk level, and missing information.',
        icon: FileSearch,
    },
    {
        key: 'recommend_assignee',
        label: 'Who to assign',
        description: 'Suggest the best specialist or role based on category, text, and AI role descriptions.',
        icon: UserCheck,
    },
    {
        key: 'recommend_response',
        label: 'Recommendations',
        description: 'Suggest next steps, checks, documents, and response direction.',
        icon: Lightbulb,
    },
    {
        key: 'translate_text',
        label: 'Translate text',
        description: 'Translate selected text or inquiry content into the current site language.',
        icon: Languages,
    },
];
