export type InquiryTab = 'all' | 'anonymous' | 'approval' | 'archived';

export type InquiryDetailTab =
    'description' | 'attachments' | 'comments' | 'history' | 'response';

export type InquiryResponseStatus =
    'draft' | 'pending_approval' | 'changes_requested' | 'approved' | 'sent';

export type InquiryResponseUser = {
    id: number;
    name: string;
    email: string;
};

export type InquiryResponseAttachment = {
    id: string;
    originalName: string;
    mimeType: string;
    extension: string | null;
    sizeBytes: number;
    uploadedAt: string;
    downloadUrl: string;
};

export type InquiryResponse = {
    id: number;
    status: InquiryResponseStatus;
    outcomeId: number | null;
    outcomeName: string | null;
    body: string | null;
    author: InquiryResponseUser | null;
    reviewer: InquiryResponseUser | null;
    reviewedBy: InquiryResponseUser | null;
    sentBy: InquiryResponseUser | null;
    reviewComment: string | null;
    submittedAt: string | null;
    reviewedAt: string | null;
    sentAt: string | null;
    attachments: InquiryResponseAttachment[];
};

export type InquiryHistoryUserSnapshot = {
    id: number;
    name: string;
    role?: string | null;
};

export type InquiryHistoryCategorySnapshot = {
    id: number;
    name: string;
    review_days?: number;
    review_due_date?: string;
};

export type InquiryHistoryMetadata = {
    from?: InquiryHistoryUserSnapshot | InquiryHistoryCategorySnapshot | null;
    to?: InquiryHistoryUserSnapshot | InquiryHistoryCategorySnapshot | null;
    category?: InquiryHistoryCategorySnapshot;
    type?: InquiryType;
    status?: InquiryStatus;
    status_from?: string | null;
    status_to?: string | null;
    inquiry_status_from?: InquiryStatus;
    inquiry_status_to?: InquiryStatus;
    outcome_id?: number | null;
    outcome_name?: string | null;
    reviewer?: InquiryHistoryUserSnapshot;
    comment?: string | null;
    report_id?: string;
    backfilled?: boolean;
};

export type InquiryHistoryEvent = {
    id: number;
    type: string;
    actorName: string | null;
    actorRole: string | null;
    metadata: InquiryHistoryMetadata;
    date: string;
    time: string;
    createdAt: string;
};

export type InquiryComment = {
    id: string;
    body: string;
    authorName: string | null;
    authorRole: string | null;
    source: string;
    createdAt: string;
    deleted: boolean;
    canDelete: boolean;
    attachments: InquiryCommentAttachment[];
    replies: InquiryComment[];
};

export type InquiryCommentAttachment = {
    id: string;
    originalName: string;
    extension: string | null;
    sizeBytes: number;
    downloadUrl: string;
};

export type InquiryCommentsPage = {
    data: InquiryComment[];
    currentPage: number;
    lastPage: number;
    total: number;
};

export type InquiryOutcomeOption = {
    id: number;
    name: string;
    description: string | null;
};

export type InquiryResponsePermissions = {
    respond: boolean;
    review: boolean;
    send: boolean;
    comment: boolean;
};

export type InquiryStatus =
    | 'new'
    | 'in_progress'
    | 'suspended'
    | 'completed'
    | 'rejected'
    | 'withdrawn';

export type InquiryType = 'identified' | 'anonymous';

export type InquiryCategory = {
    id: number;
    name: string;
    reviewDays: number;
};

export type InquiryRecord = {
    id: number;
    number: string;
    type: InquiryType;
    status: InquiryStatus;
    daysLeft: number;
    subject: string;
    categoryId: number | null;
    categoryName: string;
    submittedDate: string;
    submittedAt: string;
    anonymous: boolean;
    archived: boolean;
};

export type InquiryAssigneeOption = {
    id: number;
    name: string;
    email: string;
    role: string | null;
};

export type InquiryAttachment = {
    id: number;
    originalName: string;
    mimeType: string;
    extension: string | null;
    fileType:
        | 'photo'
        | 'document'
        | 'spreadsheet'
        | 'text'
        | 'pdf'
        | 'audio'
        | 'other';
    sizeBytes: number;
    uploadedAt: string;
};

export type InquiryDetail = {
    id: number;
    number: string;
    type: InquiryType;
    status: InquiryStatus;
    subject: string;
    description: string | null;
    categoryId: number;
    categoryName: string;
    submittedAt: string;
    reviewDueDate: string;
    source: string;
    applicantName: string | null;
    applicantPhone: string | null;
    assignee: InquiryAssigneeOption | null;
    location: string | null;
    attachmentsCount: number;
    attachments: InquiryAttachment[];
    commentsCount: number;
    comments: InquiryCommentsPage;
    historyCount: number;
    history: InquiryHistoryEvent[];
    response: InquiryResponse | null;
};

export type ScrollInquiries = {
    data: InquiryRecord[];
    nextPage?: number | null;
    previousPage?: number | null;
    currentPage?: number | null;
    total?: number;
};

export type InquiryFilterOption = {
    label: string;
    value: string;
};
