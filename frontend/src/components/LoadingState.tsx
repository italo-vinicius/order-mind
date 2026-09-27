import { LoaderCircle } from 'lucide-react'

export function LoadingState({ label }: { label: string }) {
  return (
    <div
      className="flex min-h-24 items-center gap-3 rounded-2xl bg-white p-5 text-sm text-[#52675f] shadow-sm"
      role="status"
      aria-live="polite"
      aria-busy="true"
    >
      <LoaderCircle className="size-4 motion-safe:animate-spin" aria-hidden="true" />
      <span className="h-4 w-24 animate-pulse rounded bg-[#173c34]/10" aria-hidden="true" />
      {label}
    </div>
  )
}
