import React from "react";

class Block extends React.Component
{
    constructor(props)
    {
        super(props);
    }

    render()
    {
        return (
            <div className="niwhiboutik-block my-3">
                {this.props.children}
            </div>
        );
    }
}

export default Block;