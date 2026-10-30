export default function AppLogoIcon({ className }: { className?: string }) {
    return (
        <img
            src="/images/logo.jpeg"
            alt="Gas Station Logo"
            className={className}
            style={{ objectFit: 'contain' }}
        />
    );
}
