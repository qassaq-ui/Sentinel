export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    can: {
        settingsAccess: boolean;
        rolesView: boolean;
        rolesCreate: boolean;
        rolesUpdate: boolean;
        rolesDelete: boolean;
        rolesPermissionsUpdate: boolean;
        inquiriesView: boolean;
        inquiriesUpdate: boolean;
        inquiriesDelete: boolean;
        inquiriesAssign: boolean;
        inquiriesRespond: boolean;
        inquiriesApprove: boolean;
        inquiriesSend: boolean;
        dictionariesView: boolean;
        dictionariesCreate: boolean;
        dictionariesUpdate: boolean;
        dictionariesDelete: boolean;
        usersView: boolean;
        usersCreate: boolean;
        usersUpdate: boolean;
        usersDelete: boolean;
    };
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */
