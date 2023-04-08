import React from 'react';
import ReactDOM from "react-dom/client";
import Wrapper from './components/Wrapper';
import PageHandler from './components/PageHandler';
import './scss/main.scss';

const root = ReactDOM.createRoot(
    document.getElementById("app")
);


root.render(
    <Wrapper>
        <PageHandler />
    </Wrapper>
);