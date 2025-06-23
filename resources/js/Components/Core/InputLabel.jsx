export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={`label` + className}
        >
            <span className={"label-text"}>
                {value ? value : children}
            </span>
        </label>
    );
}
