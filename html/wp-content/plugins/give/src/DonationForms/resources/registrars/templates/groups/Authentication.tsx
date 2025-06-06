import {FC, useState} from 'react';
import {__} from '@wordpress/i18n';
import type {FieldProps} from '@givewp/forms/propTypes';
import {GroupProps} from '@givewp/forms/propTypes';
import getWindowData from '@givewp/forms/app/utilities/getWindowData';
import postData from '@givewp/forms/app/utilities/postData';
import getCurrentFormUrlData from '@givewp/forms/app/utilities/getCurrentFormUrlData';
import FieldError from '../layouts/FieldError';
import styles from '../styles.module.scss';

const {originUrl, isEmbed, embedId} = getCurrentFormUrlData();

const getRedirectUrl = (redirectUrl: URL) => {
    const formPageUrl = new URL(originUrl);
    formPageUrl.searchParams.append('givewp-auth-redirect', 'true');

    if (isEmbed) {
        formPageUrl.searchParams.append('givewp-is-embed', String(isEmbed));
        formPageUrl.searchParams.append('givewp-embed-id', embedId);
    }

    // use wp core login redirect param
    redirectUrl.searchParams.set('redirect_to', formPageUrl.toString());

    return redirectUrl;
};
const handleLoginPageRedirected = () => {
    const formPageUrl = new URL(window.top.location.href);

    const isAuthRedirect =
        formPageUrl.searchParams.has('givewp-auth-redirect') &&
        formPageUrl.searchParams.get('givewp-auth-redirect') === 'true';

    if (isAuthRedirect) {
        const isEmbedRedirect =
            formPageUrl.searchParams.has('givewp-is-embed') &&
            formPageUrl.searchParams.get('givewp-is-embed') === 'true';
        const isThisEmbed =
            formPageUrl.searchParams.has('givewp-embed-id') &&
            formPageUrl.searchParams.get('givewp-embed-id') === embedId;

        if (isEmbedRedirect && isThisEmbed) {
            window.frameElement.scrollIntoView({
                behavior: 'smooth',
            });
        }
    }
};

handleLoginPageRedirected();

interface AuthProps extends GroupProps {
    nodeComponents: {
        login: FC<FieldProps | {}>;
        password: FC<FieldProps | {}>;
    };
    required: boolean;
    isAuthenticated: boolean;
    loginRedirect: boolean;
    loginRedirectUrl: string;
    loginNotice: string;
    loginConfirmation: string;
    lostPasswordUrl: string;
}

export default function Authentication({
    nodeComponents: {login: Login, password: Password},
    required,
    isAuthenticated,
    loginRedirect,
    loginRedirectUrl,
    loginNotice,
    loginConfirmation,
    lostPasswordUrl,
    name,
}: AuthProps) {
    const [isAuth, setIsAuth] = useState<boolean>(isAuthenticated);
    const [showLogin, setShowLogin] = useState<boolean>(required);
    const toggleShowLogin = () => setShowLogin(!showLogin);
    const redirectToLoginPage = (e) => {
        e.preventDefault();
        const loginUrl = getRedirectUrl(new URL(loginRedirectUrl));
        window.top.location.assign(loginUrl);
    };

    return (
        <>
            {isAuth && (
                <p style={{textAlign: 'center'}}>
                    <em>{loginConfirmation}</em>
                </p>
            )}
            {!isAuth && showLogin && (
                <LoginForm success={() => setIsAuth(true)} lostPasswordUrl={lostPasswordUrl} nodeName={name}>
                    <Login />
                    <Password />
                </LoginForm>
            )}
            {!isAuth && !showLogin && (
                <div style={{display: 'flex'}}>
                    {loginRedirect ? (
                        <LoginNotice onClick={redirectToLoginPage}>{loginNotice}</LoginNotice>
                    ) : (
                        <LoginNotice onClick={toggleShowLogin}>{loginNotice}</LoginNotice>
                    )}
                </div>
            )}
        </>
    );
}

/**
 * @since 4.3.0 replace "Forgot your password" anchor element with a button since it does not contain a valid href attribute.
 */
const LoginForm = ({children, success, lostPasswordUrl, nodeName}) => {
    const {authUrl} = getWindowData();
    const {useWatch, useFormContext} = window.givewp.form.hooks;
    const {setValue, getValues} = useFormContext();
    const {firstName, lastName, email} = getValues();

    const login = useWatch({name: 'login'});
    const password = useWatch({name: 'password'});

    const [errorMessage, setErrorMessage] = useState<string>('');

    const tryLogin = async (event) => {
        event.preventDefault();

        const {response} = await postData(authUrl, {
            login: login,
            password: password,
        });

        const {data: responseData} = await response.json();
        if (response.ok) {
            setValue('firstName', firstName || responseData.firstName);
            setValue('lastName', lastName || responseData.lastName);
            setValue('email', email || responseData.email);
            success();
        } else {
            setErrorMessage(
                'authentication_error' === responseData.type
                    ? responseData.message
                    : __('Something went wrong. Please try or again or contact a site administrator.', 'give')
            );
        }
    };

    return (
        <div className={styles['authentication__login-form']}>
            <div className={styles['authentication__login-form__fields-wrapper']}>{children}</div>

            {!!errorMessage && <FieldError error={errorMessage} name={nodeName} />}

            <div className={styles['authentication__login-form__buttons-wrapper']}>
                <div className={styles['authentication__login-form__reset-wrapper']}>
                    {__('Forgot your password?', 'give')}
                    <button
                        className={styles['authentication__login-form__reset-button']}
                        onClick={(event) => {
                            event.preventDefault();
                            const passwordResetUrl = getRedirectUrl(new URL(lostPasswordUrl));
                            window.top.location.assign(passwordResetUrl);
                        }}
                    >
                        <span>{__('Reset', 'give')}</span>
                    </button>
                </div>
                <button className={styles['authentication__login-form__login-button']} onClick={tryLogin}>
                    {__('Log In', 'give')}
                </button>
            </div>
        </div>
    );
};

const LoginNotice = ({children, onClick}) => {
    return (
        <button className={styles['authentication__login-notice']} onClick={onClick}>
            {children}
        </button>
    );
};
