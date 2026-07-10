export type Role = {
    id: number;
    name: string;
    label: string;
    protected: boolean;
    permissions: string[];
};

export type Permission = {
    name: string;
    group: string;
    label: string;
};
