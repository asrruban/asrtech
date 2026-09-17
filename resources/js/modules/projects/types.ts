export interface ProjectMilestone {
    id: number;
    title: string;
    description: string | null;
    status: string;
    due_date: string | null;
}
export interface ProjectApproval {
    id: number;
    title: string;
    description: string;
    status: string;
    response: string | null;
    created_at: string;
    decided_at: string | null;
}
export interface Project {
    id: number;
    title: string;
    description: string;
    status: string;
    target_date: string | null;
    client: { id: number; name: string; email: string };
    project_inquiry_id: number | null;
    quote_id: number | null;
    milestones: ProjectMilestone[];
    approvals: ProjectApproval[];
    updates: { id: number; body: string; author: string; created_at: string }[];
    files: {
        id: number;
        original_name: string;
        size: number;
        author: string;
        created_at: string;
    }[];
}
export const label = (status: string) => status.replaceAll('_', ' ');
export const date = (value: string | null) =>
    value
        ? new Date(
              value.length === 10 ? `${value}T12:00:00` : value,
          ).toLocaleDateString('en', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : 'Not set';
