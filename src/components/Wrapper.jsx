import React from 'react';
import Header from './Header';

class Wrapper extends React.Component
{
    render()
    {
        return (
            <>
                <Header />
                {this.props.children}
            </>
        );
    }
}

export default Wrapper;