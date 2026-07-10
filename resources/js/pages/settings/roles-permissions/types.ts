export type Role = {
    id: number;
    uuid: string | null;
    name: string;
    label_key: string | null;
    fallback_label: string | null;
    ai_description: string | null;
    label: string;
    protected: boolean;
    permissions: string[];
};

export type Permission = {
    name: string;
    group: string;
    label: string;
};
