export type InquiryTab = 'all' | 'anonymous' | 'archived';

export type InquiryDetailTab = 'description' | 'attachments' | 'comments' | 'history';

export type InquiryStatus =
    | 'new'
    | 'in_progress'
    | 'suspended'
    | 'completed'
    | 'rejected'
    | 'withdrawn';

export type InquiryType = 'portal' | 'anonymous';

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
    fileType: 'photo' | 'document' | 'spreadsheet' | 'text' | 'pdf' | 'audio' | 'other';
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
    historyCount: number;
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
